<?php
namespace quayph\quaffa\config;

/**
 * Default values for _defaultJobConfig.yaml
 */
class DefaultJobConfig {

    /**
     * Default values for _defaultJobConfig.yaml
     *
     * @return array Default values
     */
    public static function getConfig() {
        return [
            // 'automountDevice' => '',
            // 'automountOptions' => '',
            // 'automountPoint' => '',
            // 'autoUnmount' => '',
            'bandwidthLimit' => 0,
            'connectionCheckTimeout' => 120,
            'debug' => false,
            'description' => '',
            'diskSpaceWarn' => 85,
            'extraEmailNotifications' => [],
            'exclude' => [],
            'include' => [],
            'jobName' => '',
            'keepDaily' => 7,
            'keepMonthly' => 12,
            'keepScheduled' => 2,
            'keepWeekly' => 4,
            'keepYearly' => 0,
            'localNice' => 0,
            'postJobCommand' => [],
            'postJobCommandRemote' => [],
            'preJobCommand' => [],
            'preJobCommandRemote' => [],
            'remoteAuthorizedKeysFile' => ".ssh/authorized_keys",
            'remoteHostName' => '',
            'remoteNice' => 0,
            'remoteNiceBinary' => "/bin/nice",
            'remoteRsyncBinary' => "/usr/bin/rsync",
            'remoteUser' => 'root',
            'retryAfter' => 900,
            'retryAttempts' => 3,
            'retryNotification' => false,
            'rootDir' => "/var/quaffa",
            'rsyncCompress' => true,
            'rsyncInplace' => true,
            'rsyncOptions' => '',
            'rsyncRemote' => "/usr/bin/rsync",
            'rsyncTimeout' => 900,
            'sshOpts' => '',
            'sshPort' => 22,
            'enabled' => true,
            'timeSchedule' => [],
        ];
    }
}