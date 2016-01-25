<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Controller;

use AutoDeploy\AutoDeploy;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;

class Zf2Controller extends AbstractActionController
{
    /**
     * @param MvcEvent $event
     *
     * @return parent::onDispatch
     */
    public function onDispatch(MvcEvent $event)
    {
        $request = $event->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');

        // check IP address is allowed
        $application = $event->getApplication();
        $config = $application->getConfig();
        $autoDeployConfig = $config['auto_deploy'];
        $allowedIpAddresses = $autoDeployConfig['ipAddresses'];

        // error if ip is not allowed
        if (!in_array($remoteAddr, $allowedIpAddresses, true)) {
            $baseModel = new \Zend\View\Model\ViewModel();
            $baseModel->setTemplate('layout/output');

            $model = new \Zend\View\Model\ViewModel();
            $model->setTemplate('error/403');

            $baseModel->addChild($model);
            $baseModel->setTerminal(true);

            $event->setViewModel($baseModel);

            $response = $event->getResponse();
            $response->setStatusCode(403);
            $response->sendHeaders();
            $event->setResponse($response);
            exit;
        }

        return parent::onDispatch($event);
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     *
     * @throws \AutoDeploy\Exception\InvalidArgumentException
     */
    public function indexAction()
    {
        $this->layout('layout/output.phtml');

        $autoDeploy = new AutoDeploy($this->getApplication()->getConfig());
        $autoDeploy->run();

        $response = $this->getResponse();
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * @return \Zend_Application
     */
    protected function getApplication()
    {
        if ($this->application === null) {
            $this->application = $this->getServiceLocator()->get('Application');
        }

        return $this->application;
    }
}
