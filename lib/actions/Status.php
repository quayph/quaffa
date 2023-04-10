<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\ReportsManager;
use quayph\quaffa\JobLockManager;
use quayph\quaffa\OutputHelper;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use dekor\ArrayToTextTable;

/**
 * Show the status of all configured backup jobs
 */
class Status extends AbstractAction {
    
    /**
     * Constructor: Configures class vairables and executes the job's tasks
     *
     * @param array $context When and how the command was invoked
     */    
    public function __construtor($context) {
        $this->context = $context;
        $this->execute();
    }

    /**
     * Shows the status of all configured backup jobs
     * 
     * @return void
     */
    public function execute() {
        $statuses = [];        
        foreach ($this->jobs as $jobname => $jobconf) {
            $data = [];
            $data['Name'] = $jobname;
            $data['Next'] = $this->getNext($jobconf);
            $reports = ReportsManager::getAllExistingReportsFromRootDir($jobconf->rootDir.DS.$jobname);
            if (count($reports)) {
                $last = array_pop($reports);
                $data['Since'] = $this->getTimeSinceLastBackup($last);
                $data['Delta'] = $this->getSizeFilesTransfered($last);
                $data['Size'] = $this->getTotalSizeLastBackup($last);
            }
            else {
                $data['Since'] = '';
                $data['Delta'] = '';
                $data['Size'] = '';
            }
            $statuses[] = $data;
        }
        if (count($statuses)) {
            echo (new ArrayToTextTable($statuses))->render();
        }
        else {
            echo "\nNo configured backups found.";
        }
        echo "\n";
    }

    /**
     * Get the time of the next scheduled backup
     * 
     * @return string The time of the next backup in Hi format| Disabled | Running
     */
    function getNext($jobconf) {
        if (!$jobconf->enabled) {
            return 'Disabled';
        }
        if (JobLockManager::getPid($jobconf->jobName)) {
            return 'Running';       
        }
        $now = date('Hi');
        $times = $jobconf->timeSchedule;
        if (is_scalar($times)) {
            $times = [$times];
        }
        sort($times);
        $next = '';
        foreach ($times as $time) {
            $next = $time;
            if ($now > $time) {
                break;
            }
        }
        return str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the time since the start of the last backup that completed successfully
     *
     * @param \stdClass $last The backup report of the last backup that completed successfully
     * @return string The time since the backup | Never
     */
    function getTimeSinceLastBackup($last) {
        if (!property_exists($last, 'started') || !trim($last->started)) {
            return 'Never';
        }
        return CarbonImmutable::parse($last->started)->diffForHumans([
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            'parts' => 2,
            'short' => true,
        ]);
    }

    /**
     * Get the total size of all transfered files in human readable form 
     *
     * @param \stdClass $last The backup report of the last backup that completed successfully
     * @return string The size of all transfered files
     */
    function getSizeFilesTransfered($last) {
        if (!property_exists($last, 'Total_transferred_file_size') || !trim($last->Total_transferred_file_size)) {
            return '';
        }
        return OutputHelper::bytesToHuman($last->Total_transferred_file_size);
    }

    /**
     * Get the total size of the last backup in human readable form 
     *
     * @param \stdClass $last The backup report of the last backup that completed successfully
     * @return string The size of the backup
     */
    function getTotalSizeLastBackup($last) {
        if (!property_exists($last, 'Total_file_size') || !trim($last->Total_file_size)) {
            return '';
        }
        return OutputHelper::bytesToHuman($last->Total_file_size);
    }
}