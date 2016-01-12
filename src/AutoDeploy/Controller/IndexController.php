<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Controller;

use AutoDeploy\Application\SystemEmailInterface;
use AutoDeploy\Vcs\Exception\Exception;
use AutoDeploy\Vcs\VcsFactory;
use Zend\Json\Exception\RuntimeException;
use Zend\Json\Json;
use Zend\Log\Exception\InvalidArgumentException;
use Zend\Log\Logger;
use Zend\Log\LoggerInterface;
use Zend\Log\Writer\Stream;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var \Zend_Application
     */
    protected $application;

    /**
     * @return \Zend\Stdlib\ResponseInterface
     *
     * @throws \AutoDeploy\Vcs\Exception\InvalidArgumentException
     */
    public function indexAction()
    {
        $this->layout('layout/output.phtml');

        // get request
        $request = file_get_contents('php://input');

        if (!$request) {
            $message = 'No request found';
            $this->log($message, $request, true);
            exit;
        }

        $application = $this->getServiceLocator()->get('Application');
        $config = $application->getConfig();
        $autoDeployConfig = $config['auto_deploy'];

        try {
            $request = Json::decode($request);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), $request, true);
            exit;
        }

        // We need auto_deploy config to be set
        if (!$autoDeployConfig) {
            $message = 'No \'auto_deploy\' config found';
            $this->log($message, $request, true);
            exit;
        }

        try {
            $vcs = VcsFactory::factory($autoDeployConfig['vcs']);
            $vcs->run();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), $request, true);
            exit;
        }

        // create log message
        // we can assume that the config branch is correct at this point
        $log = "Branch: " . $autoDeployConfig['vcs']['branch'] . "\n"
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
        // test
        $log .= $vcs->getLog();

        $this->log($log);
        $this->mailLog($log, $request);

        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent($log);
        return $response;
    }

    /**
     * Log output
     *
     * @param string $message
     * @param boolean $error
     * @return void
     */
    protected function log($message, $error = false)
    {
        $application = $this->getApplication();
        $autoDeployConfig = $application->getConfig()['auto_deploy'];

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
     * @param boolean $error
     * @return void
     */
    protected function mailLog($message = '', \stdClass $request)
    {
        $application = $this->getApplication();
        $autoDeployConfig = $application->getConfig()['auto_deploy'];

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
        $mail = new $autoDeployConfig['log']['mailerClass']($application->getConfig()['application']['email']);

        if (!$mail instanceof SystemEmailInterface) {
            throw new InvalidArgumentException(sprintf(
                'class "%s" registered as mailer does not implement AutoDeploy/Application/SystemEmail',
                $autoDeployConfig['log']['mailerClass']
            ));
        }

        $mail->send($recipients, $subject, $message);
    }

    protected function getApplication()
    {
        if ($this->application === null) {
            $this->application = $this->getServiceLocator()->get('Application');
        }

        return $this->application;
    }
}
