<?php
namespace quayph\quaffa;

use quayph\quaffa\config\Config;
use SleekDB\Store;

/**
 * Summary of ReportsManager
 */
class ReportsManager {

    static function storeStats($backup) {
        $ok = true;
        $reportFile = $backup->finalDirName.DS.'.quaffa';
        if(!file_put_contents($reportFile, json_encode($backup->report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ))) {
            $backup->logger->warning('Error writing report file : '.$reportFile);
            $backup->globalLogger->warning('Error writing report file : '.$reportFile, $backup->context);
            $ok = false;
        }
        $output = self::recordStats($backup);
        if(!$output) {
            $backup->logger->warning('Error storing backup statistics');
            $backup->globalLogger->warning('Error storing backup statistics', $backup->context);
            $ok = false;
        }
        return $ok;
    }

    static function getAllExistingReports($backup) {
        return self::getAllExistingReportsFromRootDir($backup->backupDir());
    }

    /**
     * Summary of getAllFromDir
     * @param mixed $directory
     * @return array
     */
    public static function getAllExistingReportsFromRootDir($rootDir) {
        $reports = [];
        if (file_exists($rootDir)) {
            $dir = dir($rootDir);
            while (false !== ($entry = $dir->read())) {
                if (substr($entry, 0, 1) == '.') continue;
                if (!is_dir($dir->path.DS.$entry)) continue;
                $reportfile = $dir->path.DS.$entry.DS.'.quaffa';
                if (file_exists($reportfile)) {
                    $reportfilecontent = file_get_contents($reportfile);
                    $report = json_decode($reportfilecontent);
                    $report->directory = $dir->path.DS.$entry; 
                    if (is_object($report) && property_exists($report, 'started') && $report->started) {
                        $reports[$report->started] = $report;
                    }
                }
            }
        }
        ksort($reports);
        return $reports;  
    }

    static function getAllBackupDirs($backup) {
        return self::getAllBackupDirsFromRootDir($backup->backupDir());
    }

    /**
     * Summary of getAllBackupDirsFromRootDir
     * @param mixed $directory
     * @return array
     */
    public static function getAllBackupDirsFromRootDir($rootDir) {
        $backupDirs = [];
        if (file_exists($rootDir)) {
            $dir = dir($rootDir);
            while (false !== ($entry = $dir->read())) {
                if (substr($entry, 0, 1) == '.') continue;
                if (!is_dir($dir->path.DS.$entry)) continue;
                $reportfile = $dir->path.DS.$entry.DS.'.quaffa';
                if (file_exists($reportfile)) {
                    $reportfilecontent = file_get_contents($reportfile);
                    $report = json_decode($reportfilecontent); 
                    if (is_object($report) && property_exists($report, 'started') && $report->started) {
                        $backupDirs[$report->started] = $dir->path.DS.$entry;
                    }
                }
            }
        }
        ksort($backupDirs);
        return $backupDirs;  
    }

    public static function getAllExistingDirectoriesById($rootDir) {
        $backupDirs = [];
        if (file_exists($rootDir)) {
            $dir = dir($rootDir);
            while (false !== ($entry = $dir->read())) {
                if (substr($entry, 0, 1) == '.') continue;
                if (!is_dir($dir->path.DS.$entry)) continue;
                $reportfile = $dir->path.DS.$entry.DS.'.quaffa';
                if (file_exists($reportfile)) {
                    $reportfilecontent = file_get_contents($reportfile); 
                    $report = json_decode($reportfilecontent); 
                    if (is_object($report) && property_exists($report, '_id') && $report->_id) {
                        $backupDirs[$report->id] = $dir->path.DS.$entry;
                    }
                }
            }
        }
        ksort($backupDirs);
        return $backupDirs;  

    }
    static function createRecord($backup) {
        $historyStore = new Store("history", Config::$historyDbDirectory, self::getSleekDbConfig());
        $result =  $historyStore->insert(['jobName' => $backup->jobname, 'report' => $backup->report]);
        return $result ? $result['_id'] : false;
    }

    static function recordStats($backup) {
        $historyStore = new Store("history", Config::$historyDbDirectory, self::getSleekDbConfig());
        return $historyStore->updateById($backup->_id, ['report' => $backup->report]);
    }

    private static function getSleekDbConfig() {
        return [
            "timeout" => false,
            "folder_permissions" => 0700
        ];
    }
    static function getHistory($jobname) {
        $historyStore = new Store("history", Config::$historyDbDirectory, self::getSleekDbConfig());
        //return $historyStore->findAll();
        return $historyStore->findBy(['jobname', '=', $jobname], ["report.started" => "desc"], 100);
    }
}