<?php
namespace quayph\quaffa\actions;

/**
 * Kill all running backups
 */
class Killall extends AbstractAction {
    
    /**
     * Constructor: Configures class vairables and executes the job's tasks
     *
     * @param array $context When and how the command was invoked
     */
    public function __construct($context) {
        parent::__construct( $context );
        $this->execute();
    }

    /**
     * Kills all running backup jobs
     * @throws \Exception on error
     * @return void
     */
    public function execute() {
        foreach ($this->jobs as $jobname => $jobconf) {
            new Kill($this->context);
        }
    }
}