<?php
namespace quayph\quaffa;

use quayph\quaffa\config\AppConfig;
use Symfony\Component\Yaml\Yaml;

//TODO : This class is not used or ready yet !
class AppConfigManager extends AppConfig {
    
    private static $confFile = APPDIR.DS.'config'.DS.'config.yaml';

    private static $fields = [
        'adminEmail',
        'globalDisable',
        'emailFromAddress',
        'emailFromName',
        'smtp',
        'smtpServer',
        'smtpUser',
        'smtpPasswd',
        'smtpSecure',
        'smtpPort',
    ];

    public static function setup($confFile = false) {

        if ($confFile) self::$confFile = $confFile;
        
        if (!file_exists($confFile)) {
            throw new \Exception('Config file missing : '.self::$confFile, 101);
        }
        
        $confData = Yaml::parseFile($confFile);
        
        if (!$confData) {
            throw new \Exception('Error decoding conf file : '.self::$confFile, 102); 
        }

        self::set($confData);
    }

    public static function set($configData) {
        foreach ($configData as $k => $v) {
            if (in_array($k, self::$fields)) {
                parent::$$k = $v;
            } 
        }
    }

    public static function save() {
        if (!is_writable(self::$confFile)) {
            throw new \Exception('Config file not writable: '.self::$confFile, 102);
        }
        $data = [];
        foreach (self::$fields as $field) {
            $data[$field] = self::$$field;
        }
        if (!file_put_contents(self::$confFile, Yaml::dump($data))) {
            throw new \Exception('Error writing config file : '.self::$confFile, 102);
        }
    }

 
}