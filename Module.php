<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;


class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include 'config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function preDispatch($event)
    {
        $request = $event->getRequest();
        $remoteAddr = $request->getServer('REMOTE_ADDR');

        // check IP address is allowed
        $application = $event->getApplication();
        $config = $application->getConfig();
        $autoDeployConfig = $config['auto_deploy'];
        $allowedIpAddresses = $autoDeployConfig['allowedIpAddresses'];

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
    }
}
