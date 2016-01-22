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

class IndexController extends AbstractActionController
{
    /**
     * @return \Zend\Stdlib\ResponseInterface
     *
     * @throws \AutoDeploy\Exception\InvalidArgumentException
     */
    public function indexAction()
    {
        $this->layout('layout/output.phtml');

        try {
            $autoDeploy = new AutoDeploy($this->getApplication()->getConfig());
            $log = $autoDeploy->run();
        } catch (\Exception $e) {

        }

        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent($log);
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
