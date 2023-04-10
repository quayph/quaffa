<?php
namespace quayph\quaffa;
use quayph\quaffa\config\AppConfig;
use quayph\quaffa\config\Config;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class that actually performs the backup
 */
class BackupAgent {
    
    /**
     * When and how the command was invoked
     *
     * @var array
     */
    private array $context;

    /**
     * The id of the backup
     *
     * @var integer
     */
    public int $_id;

    /**
     * Output from the rsync command, contains statistics about the backup
     *
     * @var array
     */
    public array $rsyncOutput;

    /**
     * Array holdin the backup report
     *
     * @var array
     */
    public array $report = [];

    /**
     * Configuration of the backup job
     *
     * @var \stdClass
     */
    public \stdClass $jobconf;

    /**
     * Named Monolog instance
     *
     * @var Logger
     */
    private Logger $logger;
    
    /**
     * Global Monolog instance
     *
     * @var Logger
     */
    private Logger $globalLogger;

    /** 
     * Name of the backup job 
     *
     * @var string 
     */
    public string $jobname = '';

    /**
     * Name of the directory containing the backup
     *
     * @var string
     */
    public string $finalDirName = '';
    
    /**
     * Constructor : performe the backup
     */
    public function __construct() {

        $this->readInput();

        $this->context['started'] = new CarbonImmutable($this->context['started'], date_default_timezone_get());
        $this->report['started'] = $this->context['started']->format('c', date_default_timezone_get());
        $this->report['invokedBy'] = join(' ', $this->context['argv']);    
        
        AppConfig::setup();

        $logLevel = $this->getLogLevel();
        $this->globalLogger = new Logger($this->jobname);
        $this->globalLogger->pushHandler(new StreamHandler(Config::$logDir.DS.Config::$globalLogFile, $logLevel));

        $this->logger = new Logger($this->jobname);
        $this->logger->pushHandler(new StreamHandler(Config::$logDir.DS.$this->jobname.'.log', $logLevel));
        
        try {
            $this->initRecord();
            $this->lockJob();
            $this->doPreJobCmds();
            $this->doPreJobRemoteCmds();
            $this->rsync();
            $this->parseRsyncOutput();
            $this->doPostJobRemoteCmds();
            $this->doPostJobCmds();
            $this->renameBackupDir();
            $this->pruneBackups();
        }
        catch (\Exception $e) {
            $this->logger->error($e->__toString());
            throw $e;
        }
        finally {
            try {
                $this->removeLock();
            }
            catch (\Exception $e) {
                $this->logger->error($e->__toString());
                throw $e;
            }
            finally {
                try {
                    $this->storeStats();
                }
                catch (\Exception $e) {
                    $this->logger->error($e->__toString());
                    throw $e;
                }
                finally {
                    $this->notify();
                }
            }
        }
    }

    /**
     * Writes a new backup record to the database and stores its id
     *
     * @return void
     */
    function initRecord() {
        $this->_id = ReportsManager::createRecord($this);
    }

    /**
     * Parses the command line argument for the job configuration
     *
     * @throws \Exception if the argument is missing or not parsable json
     * @return void
     */
    function readInput() {

        if(!array_key_exists(1, $_SERVER['argv'])) {
            throw new \Exception('Error: expecting an argument');
        }
        if (!$input = json_decode($_SERVER['argv'][1], JSON_OBJECT_AS_ARRAY)) {
            throw new \Exception('Error: expecting a valid json string as argument');
        }
        $this->jobconf = (object) $input['jobconf'];
        $this->context = $input['context'];
        $this->jobname = $this->jobconf->jobName;      
    }

    /**
     * Places a lock file for the job
     *
     * @throws \Exception if the job is locked
     * @return void
     */
    function lockJob() {
       if (!JobLockManager::lock($this->jobname)) {
            throw new \Exception('Error obtaining lock for the backup');
       };
    }

    /**
     * Executes configured pre job commands on the local server
     *
     * @return void
     */
    function doPreJobCmds() {
        $scripts = $this->jobconf->preJobCommand;
        if (is_scalar($scripts)) $scripts = [$scripts];
        foreach ( $scripts as $script) {
            $this->doLocalCmd($script);
        }
    }

    /**
     * Executes configured pre job commands on the remote server
     *
     * @return void
     */
    function doPreJobRemoteCmds() {
        $scripts = $this->jobconf->preJobCommandRemote;
        if (is_scalar($scripts)) $scripts = [$scripts];
        foreach ( $scripts as $script) {
            $this->doRemoteCmd($script);
        }
    }

    /**
     * Builds and executes the rsync command
     *
     * @return void
     */
    function rsync() {
        
        $command = $this->getRsyncCommand();
        $this->logger->debug($command);
        
        $this->rsyncOutput = [];
        $return_value = null;
        $rsyncStarted = time();
        exec($command, $this->rsyncOutput, $return_value);
        $msg = 'Rsync terminated with return value '.$return_value;
        $this->report['rsync_time'] = time() - $rsyncStarted;
        if (!in_array($return_value, [0, 23])) {
            $this->logger->error($msg);
            $this->report['rsync_error'] = $msg;
            throw new \Exception($msg);
        }
        else {
            $this->logger->info($msg);
        }
    }

    /**
     * Parses the output from rsync
     *
     * @return void
     */
    function parseRsyncOutput() {
        $this->logger->debug('Parsing rsync output');
        $this->logger->debug(print_r($this->rsyncOutput, true));
        $wantedRsyncReportValues = [
            'Number of files',
            'Number of files transferred',
            'Total file size',
            'Total transferred file size',
            'Literal data',
            'Matched data',
            'File list size',
            'File list generation time',
            'File list transfer time',
            'Total bytes sent',
            'Total bytes received',
        ];

        foreach ( $this->rsyncOutput as $line ) {
            $line = trim($line); 
            if ($line) {
                $tmp = explode(':', $line);
                $key = str_replace(' ', '_', trim($tmp[0]));
                if (in_array( $tmp[0], $wantedRsyncReportValues)) {
                    $val_with_units = explode(' ', trim($tmp[1]));
                    $this->report[$key] = str_replace(',', '', $val_with_units[0]);
                } 
            }
        }
        $this->logger->debug(print_r($this->report, true));
    }

    /**
     * Executes configured post job commands on the local server
     *
     * @return void
     */
    function doPostJobCmds() {
        $scripts = $this->jobconf->postJobCommand;
        if (is_scalar($scripts)) $scripts = [$scripts];
        foreach ( $scripts as $script) {
            $this->doLocalCmd($script);
        }
    }

    /**
     * Executes configured post job commands on the remote server
     *
     * @return void
     */
    function doPostJobRemoteCmds() {
        $scripts = $this->jobconf->postJobCommandRemote;
        if (is_scalar($scripts)) $scripts = [$scripts];
        foreach ( $scripts as $script) {
            $this->doRemoteCmd($script);
        }
    }

    /**
     * Renames the backup directory to its final name in date format Y-m-d_His
     *
     * @throws \Exception on failure
     * @return void
     */
    function renameBackupDir() {
        $this->logger->debug('Renaming backup directory');
        $this->finalDirName = $this->getUniqueFileName(
            $this->backupDir().DS.$this->context['started']->format('Y-m-d_His')
        );
        $cmd = "mv -T ".$this->backupDir().DS.'running '.$this->finalDirName;
        $this->logger->debug($cmd);
        system($cmd, $return);
        if ($return) {
            throw new \Exception('Error renaming backup folder "running" to '. $this->finalDirName);
        }
    }

    /**
     * Deletes unwanted backups to comply with the configured retention schedule
     * See keepScheduled, keepDaily, keepWeekly, keepMonthly and keepYearly cofiguration values
     * @return void
     */
    function pruneBackups() {
        $this->logger->debug('Pruning backups');
        $times = $this->jobconf->timeSchedule;
        if(is_scalar($times)) {
            $times = [$times];
        }
        $keepScheduled = $this->jobconf->keepScheduled;
        if ($keepScheduled < 1) {
            $keepScheduled = 1;
        }
        $daysForScheduled = ceil($this->jobconf->keepScheduled/count($times));
        $dailyStartsAt = CarbonImmutable::today()->subDays($daysForScheduled);
        $weeklyStartsAt = $dailyStartsAt->subDays($this->jobconf->keepDaily);
        $monthlyStartsAt = $weeklyStartsAt->subWeeks($this->jobconf->keepWeekly);
        $yearlyStartsAt = $monthlyStartsAt->subMonths($this->jobconf->keepMonthly);
        
        $backupDirs = ReportsManager::getAllBackupDirs($this);
        krsort($backupDirs);
        
        mkdir($this->getTrashDir());

        $kept = 0;
        foreach ($backupDirs as $dateStr => $dir) {

            $date = Carbon::parse($dateStr);
            $toKeep = $keepScheduled;

            if ($kept >= $toKeep && 
                $date > $dailyStartsAt && 
                $lastDate->format('Ymd') == $date->format('Ymd')) 
            {
                $this->moveToTrash($dir);
                continue;
            }

            $toKeep += $this->jobconf->keepDaily;
            if ($kept >= $toKeep && 
                $date > $weeklyStartsAt 
                && $lastDate->format('YW') == $date->format('YW')) 
            {
                $this->moveToTrash($dir);
                continue;
            }

            $toKeep += $this->jobconf->keepWeekly;
            if ($kept >= $toKeep && 
                $date > $monthlyStartsAt && 
                $lastDate->format('Ym') == $date->format('Ym')) 
            {
                $this->moveToTrash($dir);
                continue;
            }

            $toKeep += $this->jobconf->keepMonthly;
            if ($kept >= $toKeep && 
                $date > $yearlyStartsAt && 
                $lastDate->format('Y') == $date->format('Y')) 
            {
                $this->moveToTrash($dir);
                continue;
            }

            $toKeep += $this->jobconf->keepYearly;
            if ($kept >= $toKeep) 
            {
                $this->moveToTrash($dir);
                continue;
            }

            $kept++;
            $lastDate = $date;
        }

        $cmd = 'rm -rf '.$this->getTrashDir();
        $output = [];
        $return = 0;
        exec($cmd, $output, $return);
        if ($return) {
            array_unshift($output, 'Error deleting trash folder '.$this->getTrashDir());
            $this->logger->warning(join("\n", $output));
            $this->globalLogger->warning(join("\n", $output), $this->context);
        }
    } 

    /**
     * Removes the job lock file
     *
     * @throws \Exception on failure
     * @return void
     */
    function removeLock(){
        $this->logger->debug('Removing lock');
        if (!JobLockManager::unlock($this->jobname)) {
            throw new \Exception('Error removing lock for the backup');
        };
    }

    /**
     * Sets the backup finish time and stores statistics in a report file and the database
     *
     * @return void
     */
    function storeStats(){
        $this->logger->debug('Storing stats');
        $this->report['finished'] = date('c');
        $this->report['_id'] = $this->_id;
        if (!ReportsManager::storeStats($this)) {
            throw new \Exception('Error saving job statistics.');
        };
    }

    /**
     * Sends any configured notifications
     *
     * @return void
     */
    function notify() {
        $this->logger->debug('Notifying');
        if ( $error = NotificationHelper::notify($this)) {
            $this->logger->warning($error);
            $this->globalLogger->warning($error, $this->context); 
        }
    }

    // ---------------- private functions -----------------

    /**
     * Check to see if debug log level is configured globally, for the job or at runtime.
     *
     * @param boolean $jobname
     * @return boolean
     */
    private function isDebug($jobname = false) {
        if (!$jobname) {
            $jobname = $this->jobname;
        }
        return AppConfig::$logLevel == 'DEBUG' || 
            $this->jobconf->debug || 
            in_array('--debug', $this->context['argv']);
    }

    /**
     * Gets the configured runtime
     *
     * @return string the loglevel : DEBUG | INFO | NOTICE | WARNING | ERROR
     */
    private function getLogLevel() {
        $logLevel = constant("Monolog\\Logger::".AppConfig::$logLevel);
        if ($this->isDebug($this->jobname)) {
            $logLevel = Logger::DEBUG;
        }
        return $logLevel;
    }

    /**
     * Returns the filename unchanged if the file doesn't exist, or adds a suffix to ensure uniqeness.
     *
     * @param string $path The filename to check
     * @return string A unique filename
     */
    function getUniqueFileName($path) {
        $i = 1;
        $dirs = explode(DS, $path);
        $finalPart = array_pop($dirs);

        while(file_exists($path)) {
            if ($i > 1) {
                $parts = explode('.', $path);
                array_pop($parts);
                $path = join('.', $parts);
            }
            $path .= '.'.$i;
            $i++;
        }
        return $path;
    }

    /**
     * Execute a command on the local server
     *
     * @param string $script Absolute path to a script or relative path from the configured scripts directory.
     * @return void
     */
    function doLocalCmd($script) {
        $script = trim($script);
        if (!$script) return false;

        $command = $this->checkScriptExistsIsExecutable($script);
        $this->logger->debug($command);
        $output = [];
        $return = null;
        exec($command, $output, $return);
        $this->logger->notice('Remote script '.$script.' terminated with return value '.$return);
    }


    /**
     * Remove leading or trailing slashes from a string
     *
     * @param string $str
     * @return string The striing with leading and trailing slashes removed
     */
    function removeLeadingTrailingSlashes($str) {
        if (substr($str, 0, 1) == '/') {
            $str = substr($str, 1);
        }
        if (substr($str, -1) == '/') {
            $str = substr($str, 0, -1);
        }
        return $str;
    }

    /**
     * Builds the rsync command
     *
     * @return string the rsync command
     */
    private function getRsyncCommand() {
        $rsyncDest = $this->backupDir().DS.'running';
        if (!file_exists($rsyncDest)) {
            mkdir($this->backupDir().DS.'running');
        }
        $exclude = $this->getExcludedString();
        $linkDest = $this->getLinkdest();
        $remoteArgs = $this->getRemoteArgs();
        
        return join(' ', [
            ($this->jobconf->localNice ? Config::$localNiceBinary.' --adjustment='.$this->jobconf->localNice :''),
            Config::$localRsyncBinary,
            "--archive",
			"--hard-links",
			"--stats",
			"--delete-during", 
			"--ignore-errors", 
			"--delete-excluded",
			"--relative",
			"--partial",
            "--numeric-ids",
			($this->jobconf->rsyncInplace ? '--inplace' : ''),
			$remoteArgs,
			( $linkDest ? '--link-dest="'.$linkDest.'"' : '' ), 
			$this->getExcludedString(), 
			$this->jobconf->rsyncOptions,
            ($this->jobconf->remoteUser ? $this->jobconf->remoteUser.'@' : '').$this->jobconf->remoteHostName.$this->getSourceDirs(), 
			$rsyncDest 
        ]);
    }

    /**
     * Detects if the configured job is localhost
     *
     * @return boolean
     */
    function isLocalhost() {
        return in_array($this->jobconf->remoteHostName, ['localhost', '127.0.0.1', '::1']);
    }

    /**
     * Builds the options for excluding files in the rsync command
     *
     * @return string The exclude option. eg. --exclude="some/path" --exclude="some/other/path"
     */
    function getExcludedString() {
        $result = '';
        foreach($this->jobconf->exclude as $excluded) {
            if (!trim($excluded)) continue;
            $excluded = $this->removeLeadingTrailingSlashes($excluded);
            $result .= '--exclude="'.$excluded.'" ';
        }
        return trim($result);
    }

    /**
     * Builds the source directory part of the rscync command
     *
     * @return string The included directories formated for rsync
     */
    function getSourceDirs() {
        $result = '';
        foreach($this->jobconf->include as $included) {
            if (!trim($included)) continue;
            if (!$this->isLocalhost()) {
                $result .= ':';
            }
            $result .= $included.' ';
        }
        return trim($result);
    }

    /**
     * Gets the directory for the --link-dest option for the rsync command
     *
     * @return string The path of the directory containing the most recent successful backup, or an empty string.
     */
    function getLinkdest() {
        $backupDirs = ReportsManager::getAllBackupDirs($this);
        if (!count($backupDirs)) return '';
        ksort($backupDirs);
        return array_pop($backupDirs);
    } 

    /**
     * Builds the rsync options nessessary to connect to the remote server.
     *
     * @return string rsync options | an empty string if for a local backup.
     */
    function getRemoteArgs() {
        if ($this->isLocalhost()) {
            $remoteArgs = '';
        }
        else {
            $remoteArgs = '--rsync-path="';
            if ($this->jobconf->remoteNice) {
                $remoteArgs .= $this->jobconf->remoteNiceBinary.' --adjustment='.$this->jobconf->remoteNice;
            }
            $remoteArgs .= $this->jobconf->remoteRsyncBinary.'" ';
            $remoteArgs .= ($this->jobconf->bandwidthLimit ? ' --bwlimit='.$this->jobconf->bandwidthLimit : '');
            $remoteArgs .= ($this->jobconf->rsyncTimeout ? ' --timeout='.$this->jobconf->rsyncTimeout : '');
            $remoteArgs .= ($this->jobconf->rsyncCompress ? ' --compress' : '');
            $remoteArgs .= ' --rsh="'.Config::$localSSHBinary.' '.$this->sshOpts().'"';
        }
        return $remoteArgs;
    }

    /**
     * Execute a script on a remote server
     *
     * @param string $script Absolute path to the script | path relative to the configured scripts directory
     * @return void
     */
    function doRemoteCmd($script) {
        $script = trim($script);
        if (!$script) return false;

        $pathToScript = $this->checkScriptExistsIsExecutable($script);
        $remoteScript = '/tmp/quaffa-'.date('YmdHis');
        
        $command = join(' ', [
            Config::$localRsyncBinary,
            "--archive",
            ($this->jobconf->rsyncTimeout ? "--timeout=".$this->jobconf->rsyncTimeout : ''),
            ($this->jobconf->rsyncCompress ? "--compress" : ''),
            '--rsync-path="'.$this->jobconf->rsyncRemote.'"',
            '--rsh="'.Config::$localSSHBinary.' '.$this->sshOpts().'"',
            $this->jobconf->rsyncOptions,
            $pathToScript,
            ($this->jobconf->remoteUser ? $this->jobconf->remoteUser.'@' : '') . $this->jobconf->remoteHostName.':'.$remoteScript,
        ]);
        echo $command;
        $this->logger->debug($command);
        $output = [];
        $return = null;
        exec($command, $output, $return);
        $this->logger->notice('Rsync terminated with return value '.$return);

        $command = join(' ', [
            Config::$localSSHBinary.' '.$this->sshOpts(),
            ($this->jobconf->remoteUser ? $this->jobconf->remoteUser.'@' : '') . $this->jobconf->remoteHostName,
            $remoteScript,
            $this->jobconf->remoteHostName, 
            $this->jobname,
        ]);
        $this->logger->debug($command);
        $output = [];
        $return = null;
        exec($command, $output, $return);
        $this->logger->notice('Remote script '.$script.' terminated with return value '.$return);
    }

    /** 
     * Check if a script exists and is executable
     * 
     * @throws \Exception if the script is not found or not executable
     * @return string The absolute path to the script 
     */
    function checkScriptExistsIsExecutable($script) {
    
        $cmd = Config::$scriptDir.DS.$script;
        if (!file_exists($cmd)) {
            throw new \Exception('Script not found: '.$cmd);
        } 
        if (!is_executable($cmd)) {
            throw new \Exception('Script not executable: '.$cmd);
        }
        return $cmd;
    }
    
    /**
     * Get the directory containing the backups for the configured job
     *
     * @return string An absolute path to the backup directory
     */
    function backupDir() {
        return $this->jobconf->rootDir.DS.$this->jobname;
    }
    
    /**
     * Get the directory that will hold backups to be deleted
     *
     * @return string An absolute path to the trash directory
     */
    function getTrashDir() {
        return $this->backupDir().DS.'.trash';
    }

    /**
     * Move a directory to the trash directory
     *
     * @param string $dir An absolute path
     * @return void
     */
    function moveToTrash($dir) {
        $this->logger->debug('Moving directory to trash '.$dir);
        if(!rename($dir, $this->getTrashDir().DS.basename($dir))) {
            $this->logger->warning('Error moving backup to trash : '.$dir);
            $this->globalLogger->warning('Error moving backup to trash : '.$dir);
        }
    }

    /** 
     * Get the options necessary for the ssh command to connect to a remote server
     * 
     * @return string ssh options string
     */
    function sshOpts() {
		return '-p '.$this->jobconf->sshPort
            .' -o CheckHostIP=no '
            .' -o StrictHostKeyChecking=no'
            .' -o HostKeyAlias='.$this->jobname 
            .' -o UserKnownHostsFile=/root/.ssh/knownhosts-'.$this->jobname
            .($this->isDebug() ? '' : ' -q');
    }
}