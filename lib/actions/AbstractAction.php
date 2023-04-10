<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\JobConfigManager;
use Monolog\Logger;

/**
 * Base class for all actions (commands)
 */
class AbstractAction {

    public Logger $globalLogger;
    
    /**
     * Data about when and how the script was launched
     * @var array
     */
    protected array $context;

    /**
     * Array of the different jobs configured, indexed by job name
     *
     * @var array
     */
    protected array $jobs;

    /**
     * Constructor - reads the job configuration and stores when and how the script was launched 
     *
     * @param array $context
     */
    public function __construct( array $context ) {
        $this->context = $context;
        $this->jobs = JobConfigManager::getJobs();
    }

    function getArg($arg) {
        return $this->context['args']->getArg($arg);
    }

    function getOpt($opt) {
        return $this->context['args']->getOpt($opt);
    }
}