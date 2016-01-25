<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

use AutoDeploy\Application\Log;
use AutoDeploy\AutoDeploy;
use AutoDeploy\Exception\InvalidArgumentException;

abstract class AbstractService implements ServiceInterface
{
    /**
     * @var \AutoDeploy\Service\Vcs\Service
     */
    protected $vcsService;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var bool
     */
    protected $hasRun = false;

    /**
     * @var bool
     */
    protected $hasRolledBack = false;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Array
     */
    protected $config;

    /**
     * @var String
     */
    protected $type;

    /**
     * @param $service
     * @param Log $log
     */
    public function __construct($service, Log $log)
    {
        if (is_array($service)) {
            $this->parseConfig($service);
        } elseif ($service instanceof ServiceInterface) {
            // Copy constructor
            $this->setType($service->getType());
            $this->setConfig($service->getConfig());
        } elseif ($service !== null) {
            throw new InvalidArgumentException(sprintf(
                'Expecting an array or an instance of ServiceInterface, received "%s"',
                (is_object($service) ? get_class($service) : gettype($service))
            ));
        }

        $this->log = $log;
    }

    /**
     * @return AutoDeploy
     */
    public function getAutoDeploy()
    {
        return $this->autoDeploy;
    }

    /**
     * @param array $config
     */
    public function parseConfig(array $config = array())
    {
        $this->config = $config;
        $this->type = $config['type'];
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function setConfig(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Log $log
     */
    public function setLog(Log $log)
    {
        $this->log = $log;
    }

    /**
     * @return String
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param boolean $boolean
     */
    public function setHasRun($boolean = false)
    {
        $this->hasRun = $boolean;
    }

    /**
     * @return boolean
     */
    public function getHasRun()
    {
        return $this->hasRun;
    }

    /**
     * @return bool
     */
    public function getHasRolledBack()
    {
        return $this->hasRolledBack;
    }

    /**
     * @param bool $boolean
     */
    public function setHasRolledBack($boolean = false)
    {
        $this->hasRolledBack = $boolean;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->execute();
        $this->postRun();
        $this->setHasRun(true);
    }

    /**
     * @param \AutoDeploy\Service\Vcs\Service $service
     */
    public function setVcsService(\AutoDeploy\Service\Vcs\Service $service)
    {
        $this->vcsService = $service;
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @return \AutoDeploy\Service\Vcs\Service $service
     */
    public function getVcsService()
    {
        return $this->getServiceManager()->getService(
            ServiceManager::SERVICE_TYPE_VCS
        );
    }

    /**
     * @return boolean
     */
    protected function hasVcsUpdated()
    {
        return $this->getVcsService()->hasUpdated();
    }

    /**
     * This is intended to be overridden by service specific execute method
     *
     * @return void
     */
    public function execute() {}

    /**
     * This is intended to be overridden by service specific post run method
     *
     * @return void
     */
    public function postRun() {}

    /**
     * This is intended to be overridden by service specific rollback method
     *
     * @return void
     */
    public function executeRollback() {}

    /**
     * @return void
     */
    public function rollBack()
    {
        if (!$this->getHasRun()) {
            return;
        }

        $this->getLog()->addMessage('<<<<<<<< ROLL BACK');
        $this->executeRollback();
        $this->setHasRolledBack(true);
    }
}