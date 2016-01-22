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
use AutoDeploy\Service\ServiceManager;
use Zend\Json\Json;
use Zend\Log\Logger;
use Zend\Log\LoggerInterface;
use Zend\Log\Writer\Stream;

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

    public function __construct(array $config = array())
    {
        $this->config = $config;
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
     * @throws \AutoDeploy\Exception\InvalidArgumentException
     */
    public function run()
    {
        $this->preRun();

        // get request
        $request = file_get_contents('php://input');

        if (!$request) {
            $message = 'No request found';
            $this->log($message, $request, true);
            $this->mailLog($message, $request);
            exit;
        }

        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        try {
            $request = Json::decode($request);
        } catch (\Exception $e) {
            $log = $e->getMessage();
            $this->log($log, $request, true);
            $this->mailLog($log, $request);
            exit;
        }

        // We need auto_deploy config to be set
        if (!$autoDeployConfig) {
            $message = 'No \'auto_deploy\' config found';
            $this->log($message, $request, true);
            $this->mailLog($message, $request);
            exit;
        }

        try {
            $serviceManager = $this->getAutoDeployServiceManager();
            $serviceManager->run();
        } catch (\Exception $e) {
            $log = $e->getMessage() . $serviceManager->getLog();
            $this->log($log, $request, true);
            $this->mailLog($log, $request);
            exit;
        }

        // create log message
        // we can assume that the config branch is correct at this point
        $log = "Branch: " . $autoDeployConfig['services']['vcs']['branch'] . "\n"
            . "Num Commits: " . count($request->commits) . "\n"
            . "Commits:\n";

        if (is_array($request->commits)) {
            foreach ($request->commits AS $commit) {
                $log .= "\n" . $commit->timestamp
                    . "\n" . $commit->id
                    . "\n" . $commit->author->name . " - "
                    . $commit->author->email . "\n"
                    . rtrim($commit->message, "\n") . "\n";
            }
        }

        $log .= $serviceManager->getLog();

        $this->log($log);
        $this->mailLog($log, $request);
    }

    /**
     * @return ServiceManager
     */
    protected function getAutoDeployServiceManager()
    {
        if ($this->autoDeployServiceManager === null) {
            $config = $this->getConfig();
            $this->autoDeployServiceManager = new ServiceManager($config['auto_deploy']);
        }

        return $this->autoDeployServiceManager;
    }

    /**
     * Log output
     *
     * @param string $message
     * @param boolean $error
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function log($message, $error = false)
    {
        $config = $this->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        // Log is enabled by default
        if (!$autoDeployConfig['log']['enabled']) {
            return;
        }

        // log to application log
        $messageType = Logger::INFO;
        if ($error) {
            $messageType = Logger::ERR;
        }

        $logDir = APPLICATION_ROOT . DIRECTORY_SEPARATOR . $autoDeployConfig['log']['logDir'];

        // is the config missing a trailing slash?
        if (!preg_match("/\\" . DIRECTORY_SEPARATOR . "$/", $logDir)) {
            $logDir .= DIRECTORY_SEPARATOR;
        }

        $logFile = $logDir . $autoDeployConfig['log']['logFile'];

        $logger = new $autoDeployConfig['log']['logger']();

        if (!$logger instanceof LoggerInterface) {
            throw new InvalidArgumentException(sprintf(
                'class "%s" registered as logger does not implement Zend\Log\LoggerInterface',
                $logger
            ));
        }

        $writer = new Stream($logFile);
        $logger->addWriter($writer);

        $logger->log($messageType, $message);
    }

    /**
     * Log output
     *
     * @param string $message
     * @param \stdClass $request
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function mailLog($message = '', \stdClass $request)
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

        $mail->send($recipients, $subject, $message);
    }
}