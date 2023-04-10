<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\JobConfigManager;
use quayph\quaffa\CronManager;

/**
 * Updates the cron file with the curently configured jobs
 */
class UpdateCron extends AbstractAction {
    
    /**
     * The file holding the Quaffa cron jobs
     */
    const CRON_FILENAME = '/etc/cron.d/quaffa';

    /**
     * Constructor. Configures the class and executes the action 
     *
     * @param array $context When and how the command was invoked
     * @return void
     */
    public function __construtor($context) {
        $this->context = $context;
        $this->execute();
    }

    /**
     * Updates the cron file with the curently configured jobs
     *
     * @return void
     */
    public function execute() {
        if (self::configUpdatedMoreRecentlyThanCron()) {
            CronManager::updateCron($this->jobs);
        }
    }

    /**
     * Detect if any job configuration file has been updated since the last time the cron file was updated.
     *
     * @return bool
     */
    private static function configUpdatedMoreRecentlyThanCron() {
        return filemtime(self::CRON_FILENAME) < JobConfigManager::getLastJobConfigUpdateTime();
    } 
}