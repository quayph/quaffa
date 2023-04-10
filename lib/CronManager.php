<?php
namespace quayph\quaffa;

class CronManager {

    const CRON_FILENAME = '/etc/cron.d/quaffa';

    public static function updateCron($jobs) {
        
        $cronContent = self::buildCronFileContent($jobs);

        file_put_contents(self::CRON_FILENAME, $cronContent);
        chmod(self::CRON_FILENAME, 0644);
        self::restartCron();
    }

    private static function buildCronFileContent($jobs) {
        $cronfile = [];
        $cronfile[] = '# Quaffa backup server https://github.com/quayph/quaffa';
        $cronfile[] = '# Last updated '.date('Y-m-d H:i:s');
        $cronfile[] = '';
        foreach ($jobs as $jobname => $jobconf) {
            $description = $jobname.', '.$jobconf->remoteHostName.(trim($jobconf->description) ? ' : '.$jobconf->description : '');
            if ($jobconf->enabled) {
                $cronfile[] = '# '.$description;
                $cronfile[] = self::makeCronjob($jobconf);
            }
            else {
                $cronfile[] = '# DISABLED '.$description;
            }
        }
        $cronfile[] = '';
        $cronfile[] = '# update this file if needed';
        $cronfile[] = '*/15 * * * * root '.APPDIR.DS.'quaffa updatecron --cron';
        $cronfile[] = '';
        return join("\n", $cronfile);
    }

    private static function makeCronjob($jobconf) {
        $lines = [];
        $timeSchedule = $jobconf->timeSchedule;
        if (is_scalar($timeSchedule)) {
            $timeSchedule = [$timeSchedule];
        }
        foreach ($timeSchedule as $t) {
            $t = str_pad($t, 4, '0', STR_PAD_LEFT);
            $lines[] = join("\t", [
                substr($t, 2, 2).' '.substr($t, 0, 2).' * * *',
                'root',
                APPDIR.DS.'quaffa backup --cron '.$jobconf->jobName
            ]);
        }
        return join("\n", $lines);
    }

    public static function restartCron() {
        if (file_exists('/sbin/cron')) {
            `service cron restart`;
        }
        elseif(file_exists('/sbin/crond')) {
            `service crond restart`;
        }
        else {
            throw new \Exception('Cron not found');
        }
    }
}
