<?php
namespace quayph\quaffa\actions;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use dekor\ArrayToTextTable;
use quayph\quaffa\config\Config;
use quayph\quaffa\JobLockManager;
use quayph\quaffa\OutputHelper;
use quayph\quaffa\ReportsManager;

/**
 * Displays a list of backups for a job with statistics
 */
class History extends AbstractJobAction {
    
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
     * Displays a list of backups for a job with statistics
     *
     * @throws \Exception on error
     * @return void
     */
    function execute() {

        try {
            $records = ReportsManager::getHistory($this->jobname);
            $backups = ReportsManager::getAllExistingDirectoriesById($this->jobconf->rootDir.DS.$this->jobname);
            $history = [];
            if (JobLockManager::getPid($this->jobname)) {
                $history[] = [
                    'Date' => 'now',
                    'State' => 'running',
                ];
            }
            foreach ($records as $r) {
                $record = $r['report'];
                if (!$record['started']) continue;
                $h = [
                    'Date' => Carbon::parse($record['started'])->format('Y-m-d H:i:s'),
                    'State' => 'Error',
                    'Origin' => (strpos($record['invokedBy'], ' --cron ') ? 'cron':'user'),
                    'Rsync time' => '',
                    'Total time' => '',
                    'Delta' => '',
                    'Total size' => '',
                    'Directory' => '',
                ];
                if (array_key_exists('rsync_time', $record)) {
                    $h['Rsync time'] = OutputHelper::secondsToHuman($record['rsync_time']);
                }
                if (array_key_exists('finished', $record)) {
                    $h['Total time'] = $this->getDuration($record['started'], $record['finished']);
                    $h['State'] = 'Ok';
                }
                if (array_key_exists('Total_transferred_file_size', $record)) {
                    $h['Delta'] = OutputHelper::bytesToHuman($record['Total_transferred_file_size']);
                }
                if (array_key_exists('Total_file_size', $record)) {
                    $h['Total size'] = OutputHelper::bytesToHuman($record['Total_file_size']);
                }
                $path = $this->jobconf->rootDir.DS.$this->jobname;
                $dir = Carbon::parse($record['started'])->format('Y-m-d_His');
                if (file_exists($path.DS.$dir)) {
                    $h['Directory'] = $path.DS.$dir;
                }
                $history[] = $h;
            }
            if (count($history)) {
                echo (new ArrayToTextTable($history))->render();
                echo "\n";
            }
        }
        catch (\Exception $e) {
            $this->logger->error($e->__toString());
            throw $e;
        }
    }

    /**
     * Gets the time elapsed between 2 date + time strings
     *
     * @param string $started parsable date + time
     * @param string $finished parsable date + time
     * @return string The elapsed time in the format H:i:s or i:s if under 1 hour
     */
    private static function getDuration($started, $finished) {
        $seconds = Carbon::parse($finished)->format('U') - Carbon::parse($started)->format('U');
        return OutputHelper::secondsToHuman($seconds);
    }
}