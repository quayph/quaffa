<?php
namespace Quayph\Quaffa;
use quayph\quaffa\config\Config;
use quayph\quaffa\config\DefaultJobConfig;
use Symfony\Component\Yaml\Yaml;

class JobConfigManager {

    public static function getJobs() {

        $jobs = [];
        
        $defaultJobConfig = self::getDefaultJobConfig();

        foreach (glob(Config::$jobConfigDir.DS.'*.yaml') as $confFile)
        {
            if ($confFile == Config::$jobConfigDir.DS.'_defaultJobConfig.yaml') continue;

            $confData = Yaml::parseFile($confFile);
        
            if (!$confData) {
                throw new \Exception('Error decoding job configuration file : '.$confFile, 102); 
            }

            $jobconf = array_merge($defaultJobConfig, $confData);
            $jobs[$jobconf['jobName']] = (object) $jobconf;
        }
        return $jobs;
    }

    private static function getDefaultJobConfig() {
        $defaultJobConfig = DefaultJobConfig::getConfig();
        $overideFile = Config::$jobConfigDir.DS.'_defaultJobConfig.yaml';
        
        if (file_exists($overideFile)) {
            $overide = Yaml::parseFile($overideFile);
            if (count($overide)) {
                $defaultJobConfig = array_merge($defaultJobConfig, $overide);
            }
        }
        return $defaultJobConfig;
    }

    public static function getLastJobConfigUpdateTime() {
        $times = [];
        foreach (glob(Config::$jobConfigDir.DS.'*.yaml') as $confFile)
        {
            $times[] = filemtime($confFile);
        }
        return max($times);
    }

}