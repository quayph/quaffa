<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\JobLockManager;
use quayph\quaffa\config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Check's that the job can be run and launches the backup job
 */
class Backup extends AbstractJobAction {

    /**
     * Constructor: Configures class vairables and executes the job's tasks
     *
     * @param array $context When and how the command was invoked
     */
    public function __construct(array $context) {
 
        parent::__construct( $context );
        $this->execute();
    }

    /**
     * Executes the backup
     *
     * @throws \Exception on error
     * @return void
     */
    function execute() {

        try {
            $this->checkForRunningJob();
            $this->checkBackupDirWritable();
            $this->checkCnx();
            $this->runBackupAgent();
        }
        catch (\Exception $e) {
            $this->logger->error($e->__toString());
            throw $e;
        }
    }

    /**
     * Checks to see if another job is already running
     *
     * @throws Exception if another job is already running
     */
    function checkForRunningJob() {
        if (JobLockManager::getPid($this->jobname)) {
            throw new \Exception('Backup aborted. Another backup is running.');
        }
    }

    /**
     * Checks to see if the backup destination folder 
     * exists and is writable, creating it if nessessary.
     *
     * @throws \Exception if any checks fail
     * @return void
     */
    function checkBackupDirWritable() {
        if (!is_dir($this->jobconf->rootDir)) {
            if(!mkdir($this->jobconf->rootDir, 770, true)) {
                throw new \Exception('Error creating directory '.$this->jobconf->rootDir.'.');
            }
        }
        if (!is_dir($this->backupDir())) {
            if(!mkdir($this->backupDir(), 770)) {
                throw new \Exception('Error creating directory '.$this->backupDir().'.');
            }
        }
        if (!is_writable($this->backupDir())) {
            throw new \Exception('Backup destination directory not writable: '.$this->backupDir().'.');
        }
    }

    /**
     * Checks that an SSH connection to the remote server is possible.
     *
     * @throws \Exception on failure.
     * @return void
     */
    function checkCnx() {
        if ($this->isLocalhost()) {
            return;
        }
        $cmd = Config::$localSSHBinary.' '.$this->sshOpts().' '.
                $this->jobconf->remoteUser.'@'.$this->jobconf->remoteHostName." echo OK";
    
        exec($cmd, $output, $result_code);
        if ($result_code) {
            $this->logger->warning('Connection check returned error '.$result_code);
            throw new \Exception('Error executing connection check command');
        }
    }

    /**
     * Executes the backup script 'agent'
     *
     * @throws \Exception on failure.
     * @return void
     */
    function runBackupAgent() {
        $context = $this->context;
        $context['started'] = $this->context['started']->format('c');
        $arg = escapeshellarg(json_encode([
            'context' => $context,
            'jobconf' => $this->jobconf, 
        ]));

        $cmd = "nohup /usr/bin/php ".APPDIR."/bin/agent $arg > /dev/null 2>&1 &";
        $cmd = "/usr/bin/php ".APPDIR."/bin/agent $arg";
        $this->logger->debug($cmd);
        $output = [];
        $return = null;
        exec($cmd, $output, $return);
        if ($return) {
            throw new \Exception('The backup agent returned error '.$return);
        }
        Echo "\nBackup started\n";
    }

    /**
     * Gets the directory where the backups will be stored.
     *
     * @return string
     */
    public function backupDir() {
        return $this->jobconf->rootDir.DS.$this->jobname;
    }
    
}