<?php
namespace quayph\quaffa\actions;
use quayph\quaffa\config\Config;

/**
 * Send an SSH key to a remote server
 */
class Sendkey extends AbstractJobAction {
    
    /**
     * Location of the public key file
     *
     * @var string
     */
    private $publicKeyFile = "/root/.ssh/id_rsa.pub";
   
    /**
     * Location of the private key file
     *
     * @var string
     */
    private $privateKeyFile = "/root/.ssh/id_rsa";

    /**
     * Constructor: Configures class vairables and executes the job's tasks
     *
     * @param array $context When and how the command was invoked
     */
    public function __construct($context) {
        parent::__construct($context);
        $this->execute();
    }

    /**
     * Send an SSH key to the remote server specified in the job configuration
     *
     * @return void
     */
    public function execute() {
        if ($this->isLocalhost()) {
            return;
        }
        $this->generateMissingKey();
        $this->send();
    }

    /**
     * Generates a new SSH key if an existing one is not found
     *
     * @throws \Exception on error
     * @return void
     */
    function generateMissingKey() {
        if( !file_exists($this->publicKeyFile) || !file_exists($this->privateKeyFile) ) {
            
            $this->logger->notice("Generating rsa keys...");
            mkdir('/root/.ssh', 0700);
            $cmd="/usr/bin/ssh-keygen -t rsa -N '' -f ".$this->privateKeyFile;
            $output = [];
            $return = null;
            exec($cmd, $output, $return);
            if (!$return) {
                $this->logger->notice("Created rsa key pair in /root/.ssh");
            }
            else {
                throw new \Exception("Error generating rsa key pair.".join("\n", $output));
            }
        }
    }

    /**
     * Sends the SSH key to the remote host specified in the job configuration
     *
     * @throws \Exception on error
     * @return void
     */
    function send() {

        $pubk = trim(file_get_contents( $this->publicKeyFile ));
        if (!$pubk) {
            throw new \Exception("Could not open public key file ".$this->publicKeyFile.' or file empty');
        }
        $knownhostsFile = "/root/.ssh/knownhosts-".$this->jobname;
        if (file_exists($knownhostsFile)) {
            unlink($knownhostsFile);
        } 
        $mkdir = "mkdir -p /root/.ssh && chmod 700 /root/.ssh &&";

        if ($this->jobconf->remoteNice) {
            $remoteArgs += $this->jobconf->remoteNiceBinary.' --adjustment='.$this->jobconf->remoteNice;
        }
        $sshCnxCommand = Config::$localSSHBinary.' '.$this->sshOpts().'  '
            .($this->jobconf->remoteUser ? $this->jobconf->remoteUser.'@' : '')
            .$this->jobconf->remoteHostName;
        
        $cmd = join(' ', [
            '/bin/cat '.$this->publicKeyFile,
            '| '.$sshCnxCommand,
            "'/bin/cat - > /tmp/".gethostname()."\$\$ ",
            '&& mkdir -p /root/.ssh',
            '&& chmod 700 /root/.ssh',
            '&& touch '.$this->jobconf->remoteAuthorizedKeysFile,
            '&& grep -v "'.$pubk.'" < '.$this->jobconf->remoteAuthorizedKeysFile.' >> /tmp/'.gethostname()."\$\$ ;",
            "mv -f /tmp/".gethostname()."\$\$ ~/".$this->jobconf->remoteAuthorizedKeysFile."'",
        ]); 

        $this->logger->debug($cmd);
        echo "\n$cmd\n";
        exec($cmd, $output, $return);
        if($return) {
            $msg = "Sending public key to ".$this->jobconf->remoteHostName." failed.";
            $this->logger->error($msg);
            throw new \Exception($msg);
        }
        else {
            $msg = "Public key sent to ".$this->jobconf->remoteHostName;
            $this->logger->notice($msg);
            $this->globalLogger->notice($msg);
        }
    }
}
