<?php
namespace quayph\quaffa\config;

class Config {
    
    /**
    * Directory containing the quaffa program
    *
    * @var string
    */
    public static $appDir = "/opt/quaffa";

    /**
     * Name of the global log file
     *
     * @var string
     */
    public static $globalLogFile = "_GLOBAL-LOGFILE.log";

    /**
     * Directory containing the backup job configuration files 
     *
     * @var string
     */
    public static $jobConfigDir = "/etc/quaffa";

    /**
     * Path to the nice binary
     *
     * @var string
     */
    public static $localNiceBinary = "/bin/nice";

    /**
     * Path to the rsync binary
     *
     * @var string
     */
    public static $localRsyncBinary = "/usr/bin/rsync";

    /**
     * Path to the SSH client binary
     *
     * @var string
     */
    public static $localSSHBinary = "/bin/ssh";

    /**
     * Directory where the backup job lock files wil be generated
     *
     * @var string
     */
    public static $lockDir = "/var/run/quaffa";

    /**
     * Directory where the logs will be written 
     *
     * @var string
     */
    public static $logDir = "/var/log/quaffa";

    /**
     * Directory containing the executable scripts run before or after backup
     *
     * @var string
     */
    public static $scriptDir = "/etc/quaffa/scripts";

    /**
     * Directory containing the database for the backup history
     *
     * @var string
     */
    public static $historyDbDirectory = "/var/lib/quaffa";

}

