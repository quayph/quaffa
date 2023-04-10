<?php
namespace Quayph\Quaffa;

use Garden\Cli\Cli;

class CliParser {

    public static $args;
    private static $cli;
    private static $argv;

    public static function Parse() {
        self::$argv = $_SERVER['argv'];
        self::$cli = Cli::create()
        ->command('backup')
            ->description('Run a backup.')
            ->arg('jobName', 'Name of the backup job to run.', true)
        ->command('sendkey')
            ->description('Send an SSH key to the remote server.')
            ->arg('jobName', 'Name of the backup job.', true)
        ->command('kill')
            ->description('Kill a running backup.')
            ->arg('jobName', 'Name of the backup job to kill.', true)
        ->command('history')
            ->description('Display a history of the backup job. Limited to the last 100 executions.')
            ->arg('jobName', 'Name of the backup job.', true)
        ->command('killall')
            ->description('Kill all running backups.')
        ->command('status')
            ->description('Show the status of all backups.')
        ->command('updatecron')
            ->description('Creates or updates the cron file to schedule tasks.')
        ->command('*')
            ->opt('debug:d', 'Output and log debugging information.', false, 'boolean')
            ->opt('cron', 'This option is added to cron jobs. It does nothing, but is used to identify a command that has been executed by cron in the logs.', false, 'boolean');
        
        self::$args = self::$cli->parse(self::$argv);
        return self::$args;
    }

    public static function commandLine() {
        return join(' ', self::$argv);
    }
}