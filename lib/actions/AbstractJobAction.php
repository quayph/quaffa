<?php
namespace quayph\quaffa\actions;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use quayph\quaffa\config\AppConfig;
use quayph\quaffa\config\Config;

/**
 * Base class for Actions requiring a job name as a parameter
 * Sets up the job configuration and logging
 */
class AbstractJobAction extends AbstractAction {
    
    /**
     * Name of the job
     *
     * @var string
     */
    protected string $jobname;

    /**
     * The job configuration
     *
     * @var [type]
     */
    protected $jobconf;

    /**
     * Job specific Monolog instance
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Monolog instance
     *
     * @var Logger
     */
    public Logger $globalLogger;

    /**
     * Constructor : Sets up the job configuration and logging
     *
     * @param array $context
     */
    public function __construct(array $context) {
        parent::__construct( $context );
        
        $jobname = $this->context['args']->getArg('jobName');

        $logLevel = constant("Monolog\\Logger::".AppConfig::$logLevel);
        if ($this->isDebug($jobname)) {
            $logLevel = Logger::DEBUG;
        }
        $this->globalLogger = new Logger($jobname);
        $this->globalLogger->pushHandler(new StreamHandler(Config::$logDir.DS.Config::$globalLogFile, $logLevel));

        if (!array_key_exists($jobname, $this->jobs)) {
            throw new \Exception('Job "'.$jobname.'" not found.');
        }
        $this->jobname = $jobname;
        $this->jobconf = $this->jobs[$jobname];

        $this->logger = new Logger($jobname);
        $this->logger->pushHandler(new StreamHandler(Config::$logDir.DS.$jobname.'.log', $logLevel));
  
    }

    /**
     * Detect if debug is set in th global configuration file, 
     * job configuration file or if the script was called with 
     * the argument --debug 
     *
     * @param boolean $jobname
     * @return boolean
     */
    private function isDebug($jobname = false) {
        if (!$jobname) {
            $jobname = $this->jobname;
            if (in_array('--debug', $this->context['argv'])) {
                $this->jobs[$jobname]->debug = true;
            }
        }
        return AppConfig::$logLevel == 'DEBUG' || 
            (array_key_exists($jobname, $this->jobs) && 
                property_exists($this->jobs[$jobname], 'debug') && 
                $this->jobs[$jobname]->debug);
    }

    /**
     * Returns a string of options for calls to SSH 
     *
     * @return string
     */
    function sshOpts() {
		return '-p '.$this->jobconf->sshPort
            .' -o CheckHostIP=no '
            .' -o StrictHostKeyChecking=no'
            .' -o HostKeyAlias='.$this->jobname 
            .' -o UserKnownHostsFile=/root/.ssh/knownhosts-'.$this->jobname
            .($this->isDebug() ? '' : ' -q');
    }

    /**
     * Check if the backup target of the configured job is localhost
     *
     * @return boolean true if the backup target is localhost
     */
    function isLocalhost() {
        return in_array($this->jobconf->remoteHostName, ['', 'localhost', '127.0.0.1', '::1']);
    }
}