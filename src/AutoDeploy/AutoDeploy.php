<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy;

use AutoDeploy\Application\SystemEmailInterface;
use AutoDeploy\Exception\InvalidArgumentException;
use AutoDeploy\Application\Log;
use AutoDeploy\Service\ServiceManager;

class AutoDeploy
{
    /**
     * @var ServiceManager
     */
    protected $autoDeployServiceManager;

    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var Log
     */
    protected $log;

    public function __construct(array $config = array())
    {
        $this->config = $config;
        $this->log = new Log();
    }

    /**
     * Ip restriction
     */
    protected function preRun()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'];

        // check IP address is allowed
        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];
        $allowedIpAddresses = $autoDeployConfig['ipAddresses'];

        // error if ip is not allowed
        if (!is_array($allowedIpAddresses) ||
            !in_array($remoteAddr, $allowedIpAddresses, true)) {

            /**
             * @todo return 403
             */

            exit;
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Log
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @throws \AutoDeploy\Exception\InvalidArgumentException
     */
    public function run()
    {
        $this->preRun();

        // get request
        $request = file_get_contents('php://input');

        if (!$request) {
            $log = $this->getLog()
                        ->addMessage('No request found');

            $this->log($log, $request, true);
            $this->mailLog($log, $request);

            exit;
        }

        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        try {
            $request = \Zend_Json::decode($request, \Zend_Json::TYPE_OBJECT);
        } catch (\Exception $e) {
            $log = $this->getLog()
                        ->addMessage($e->getMessage());

            $this->log($log, $request, true);
            $this->mailLog($log, $request);

            exit;
        }

        // We need auto_deploy config to be set
        if (!$autoDeployConfig) {
            $log = $this->getLog()
                        ->addMessage('No \'auto_deploy\' config found');

            $this->log($log, $request, true);
            $this->mailLog($log, $request);

            exit;
        }

        try {
            $serviceManager = $this->getAutoDeployServiceManager();
            $serviceManager->run();
        } catch (\Exception $e) {
            $log = $this->getLog()
                        ->addMessage($e->getMessage());

            $this->log($log, $request, true);
            $this->mailLog($log, $request);

            exit;
        }

        // create log message
        // we can assume that the config branch is correct at this point
        $this->getLog()
             ->addMessage('Branch: ' . $autoDeployConfig['services']['vcs']['branch'])
             ->addMessage('Num Commits: ' . count($request->commits))
             ->addMessage('Commits:');

        if (is_array($request->commits)) {
            foreach ($request->commits AS $commit) {
                $this->getLog()->addMessage($commit->timestamp)
                               ->addMessage($commit->id)
                               ->addMessage($commit->author->name . " - " . $commit->author->email)
                               ->addMessage(rtrim($commit->message, "\n"));
            }
        }

        // get service manager log
        $this->getLog()->addLog($serviceManager->getLog());

        $this->log($this->getLog());
        $this->mailLog($this->getLog(), $request);
    }

    /**
     * @return ServiceManager
     */
    protected function getAutoDeployServiceManager()
    {
        if ($this->autoDeployServiceManager === null) {
            $config = $this->getConfig();
            $this->autoDeployServiceManager = new ServiceManager($config['auto_deploy'], new Log());
        }

        return $this->autoDeployServiceManager;
    }

    /**
     * Log output
     *
     * @param Log $log
     * @param boolean $error
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function log(Log $log, $error = false)
    {
        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        // Log is enabled by default
        if (!$autoDeployConfig['log']['enabled']) {
            return;
        }

        // log to application log
        $messageType = \Zend_Log::INFO;
        if ($error) {
            $messageType = \Zend_Log::ERR;
        }

        $logDir = APPLICATION_ROOT . DIRECTORY_SEPARATOR . $autoDeployConfig['log']['logDir'];

        // is the config missing a trailing slash?
        if (!preg_match("/\\" . DIRECTORY_SEPARATOR . "$/", $logDir)) {
            $logDir .= DIRECTORY_SEPARATOR;
        }

        $logFile = $logDir . $autoDeployConfig['log']['logFile'];

        $logger = new $autoDeployConfig['log']['logger']();

        /*
         * removed for zf1 migration
         *
         * if (!$logger instanceof LoggerInterface) {
            throw new InvalidArgumentException(sprintf(
                'class "%s" registered as logger does not implement Zend\Log\LoggerInterface',
                $logger
            ));
        }*/

        $writer = new \Zend_Log_Writer_Stream($logFile);
        $logger->addWriter($writer);

        $logger->log($log->getOutputString(), $messageType);
    }

    /**
     * Log output
     *
     * @param Log $log
     * @param \stdClass $request
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function mailLog(Log $log, \stdClass $request)
    {
        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        if (!$autoDeployConfig['log']['mail']) {
            return;
        }

        // get list of authors
        $authors = array();
        foreach ($request->commits AS $commit) {
            $authors[$commit->author->email] = $commit->author->name;
        }

        // set recipients
        $recipients = array_keys($authors);
        $recipients[] = $request->user_email;

        // send copy of log to recipients
        $subject = '[AutoDeploy] - ' . $_SERVER['HTTP_HOST'];
        $mail = new $autoDeployConfig['log']['mailerClass']($config['application']['email']);

        if (!$mail instanceof SystemEmailInterface) {
            throw new InvalidArgumentException(sprintf(
                'class "%s" registered as mailer does not implement AutoDeploy/Application/SystemEmail',
                $autoDeployConfig['log']['mailerClass']
            ));
        }

        $mail->send($recipients, $subject, $log->getOutputString());
    }
}