<?php
namespace quayph\quaffa;
use quayph\quaffa\config\Config;

class JobLockManager {

    public static $psCmd = 'ps -o pid= -o cmd= -p ';

    public static function getPid($jobname) {
        $lockfile = self::lockfile($jobname);
        if (file_exists($lockfile)) {
            $lockfileContent = trim(file_get_contents($lockfile));
            $parts = explode(' ', $lockfileContent);
            $pid = $parts[0];
            
            $cmd = self::$psCmd.$pid;
            $psResult = trim(`$cmd`);
            if ($psResult === $lockfileContent) {
                return $pid;
            };
            self::unlock($jobname);
        };
        return false;
    }

    public static function lock($jobname) {
        $cmd = self::$psCmd.getmypid();
        $psResult = trim(`$cmd`);
        return file_put_contents(self::lockfile($jobname), $psResult);
    }

    public static function unlock($jobname) {
        return unlink(self::lockfile($jobname));
    }

    private static function lockfile($jobname) {
        return Config::$lockDir.DS.$jobname;
    }
}