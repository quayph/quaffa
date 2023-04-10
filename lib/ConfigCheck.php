<?php
namespace quayph\quaffa;
use quayph\quaffa\config\AppConfig;
use quayph\quaffa\config\Config;

class ConfigCheck {

    public static function check($silent = true) {
        
        $error = false;
        
        if (!file_exists(CronManager::CRON_FILENAME)) {
            CronManager::updateCron([]);
        }
        if (!file_exists('/sbin/quaffa')) {
            $cmd = 'ln -s '.APPDIR.'/bin/quaffa /sbin/quaffa';
            $return = null;
            system($cmd, $return);
            if (!in_array($return, [0, 23])) {
                $msg[] = "ERROR: unable to create symbolic link to bin/quaffa at /sbin/quaffa";
                $error = true;
            }
            else {
                $msg[] = "OK: Created symbolic link to bin/quaffa at /sbin/quaffa";
            }
        }
        foreach([Config::$localNiceBinary, Config::$localRsyncBinary, Config::$localSSHBinary] as $exe) {
            if (file_exists($exe)) {
                $msg[] = "OK: Executable ".$exe." found.";
            }
            else {
                $msg[] = "ERROR: Executable ".$exe." not found.";
                $error = true;
            }                
        }

        if (!is_dir(Config::$jobConfigDir)) {
            $cmd = Config::$localRsyncBinary.' -a '.APPDIR.'/etc/ '.Config::$jobConfigDir.' && chmod -R 0700 '.Config::$jobConfigDir;
            $return = null;
            system($cmd, $return);
            if (!in_array($return, [0, 23])) {
                $msg[] = "ERROR: unable to copy config files and scripts to ".Config::$jobConfigDir;
                $error = true;
            }
            else {
                $msg[] = "OK: Config files and scripts copied to ".Config::$jobConfigDir;
            }
        }
        foreach([Config::$scriptDir, Config::$lockDir, Config::$logDir, Config::$historyDbDirectory] as $dir) {
            if (is_dir($dir)) {
                if (self::isWritable($dir)) {
                    $msg[] = "OK: Directory ".$dir." is writable.";
                }
                else {
                    $msg[] = "ERROR: Directory ".$dir." is not writable.";
                    $error = true;
                }                
            }
            else {
                if (mkdir($dir, 0770, true)) {
                    $msg[] = "OK: Directory ".$dir." created."; 
                }
                else {
                    $msg[] = "ERROR: Error creating directory ".$dir."."; 
                    $error = true;
                }
            }
        }
        

        $disabledMsg = "\nQuaffa is globally disabled. No scheduled backups will be performed.".
                        "\nReenable with the command 'quaffa enable'.";
        $msg[] =  AppConfig::$globalDisable ? $disabledMsg : '';
        
        if (!$error) {   
            if (!$silent) echo "\n".join("\n", $msg)."\n";
        }
        else {
            //self::save();
            $msg[] = "\nThere are errors in the system configuration.";
            throw new \Exception(join("\n", $msg), 1);
        }
    }

    private static function isWritable($dir) {
        return substr(exec('ls -ld '.$dir), 2, 1) == 'w';
    }
}

