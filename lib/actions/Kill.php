<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\JobLockManager;
use Monolog\Logger;

/**
 * Kills a running backup job
 */
class Kill extends AbstractJobAction {
    
    /**
     * Constructor: Configures class vairables and executes the job's tasks
     *
     * @param array $context When and how the command was invoked
     */
    public function __construct(array $context ) {
        parent::__construct( $context );
        $this->execute();
    }

    /**
     * Kills a running backup job
     * @throws \Exception on error
     * @return void
     */
    public function execute() {
        if ($pid = JobLockManager::getPid($this->jobname)) {
            $output = [];
            $return = 0;
            exec('kill -9 '.$pid);
            JobLockManager::unlock($this->jobname);
            if ($return) {
                $msg = join("\n", $output);
                $this->logger->error($msg);
                throw new \Exception($msg);
            }
            else {
                $this->logger->notice("Job killed by user.");
            }
        }
        else {
            throw new \Exception( "Backup job ".$this->jobname." not running.");
        }
    }
}