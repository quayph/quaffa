<?php
namespace quayph\quaffa\config;
use Symfony\Component\Yaml\Yaml;

/**
 * Cpplication configuration
 */
class AppConfig {
    
    /**
     * Email address to receive reports
     *
     * @var string
     */
    public static $adminEmail = '';

    /**
     * Disable all backup jobs
     *
     * @var boolean
     */
    public static $globalDisable = false;

    /**
     * The address from which emails will be sent
     *
     * @var string
     */
    public static $emailFromAddress = '';

    /**
     * The name from which emails will be sent
     *
     * @var string
     */
    public static $emailFromName = 'Quaffa backup server';
    
    /**
     * Send email confirmation of a successful backup
     *
     * @var bool
     */
    public static $emailOnSuccess = false;

    /**
     * The minimum level for logging : DEBUG | INFO | NOTICE | WARNING | ERROR
     *
     * @var string
     */
    public static $logLevel = 'DEBUG';

    /**
     * Send email using SMTP. Requires further configuration.
     *
     * @var boolean
     */
    public static $smtp = false;

    /**
     * The SMTP server to use
     *
     * @var string
     */
    public static $smtpServer = 'localhost';

    /**
     * The SMTP username
     *
     * @var string
     */
    public static $smtpUser = '';
    
    /**
     * The SMTP password
     *
     * @var string
     */
    public static $smtpPasswd = '';

    /**
     * The SMTP security : false | ssl | tls
     *
     * @var string
     */
    public static $smtpSecure = 'tls';

    /**
     * The SMTP port : 25 (non secure) | 587 (tls) | 465 (ssl) | any non standard port.
     *
     * @var integer
     */
    public static $smtpPort = 587; 
 
    /**
     * The file where modified settings are stored
     *
     * @var string
     */
    private static $confFile = APPDIR.DS.'config'.DS.'config.yaml';

    /**
     * An arry of fields that can be modified
     *
     * @var array
     */
    private static $fields = [
        'adminEmail',
        'globalDisable',
        'emailFromAddress',
        'emailFromName',
        'emailOnSuccess',
        'logLevel',
        'smtp',
        'smtpServer',
        'smtpUser',
        'smtpPasswd',
        'smtpSecure',
        'smtpPort',
    ];

    /**
     * Overide the default config values with those modified by the user 
     *
     * @param string $confFile path to a non default configuration file | false the default file path
     * @throws \Exception if the configuration file is not parsable
     * @return void
     */
    public static function setup($confFile = false) {

        if ($confFile) self::$confFile = $confFile;
        
        if (!file_exists(self::$confFile)) {
            throw new \Exception('Config file missing : '.self::$confFile, 101);
        }
        
        $confData = Yaml::parseFile(self::$confFile);
        
        if (!$confData) {
            throw new \Exception('Error decoding conf file : '.self::$confFile, 102); 
        }

        self::set($confData);
    }

    public static function set($configData) {
        foreach ($configData as $k => $v) {
            if (in_array($k, self::$fields)) {
                self::${$k} = $v;
            } 
        }
    }
}