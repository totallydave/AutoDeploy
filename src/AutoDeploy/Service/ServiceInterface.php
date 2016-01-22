<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

interface ServiceInterface
{
    /**
     * @param array $config
     */
    public function parseConfig(array $config = array());

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     *
     * @return array
     */
    public function setConfig(array $config = array());

    /**
     * @param $type
     */
    public function setType($type);

    /**
     * @return String
     */
    public function getType();

    /**
     * @param string
     */
    public function setLog($log = '');

    /**
     * @return String
     */
    public function getLog();

    /**
     * @return void
     */
    public function execute();

    /**
     * @return void
     */
    public function run();

    /**
     * @return void
     */
    public function postRun();

    /**
     * @param boolean $boolean
     *
     * @return void
     */
    public function setHasRun($boolean = false);

    /**
     * @return bool
     */
    public function getHasRun();

    /**
     * @return bool
     */
    public function getHasRolledBack();

    /**
     * @param bool $boolean
     */
    public function setHasRolledBack($boolean = false);

    /**
     * @return void
     */
    public function rollBack();

    /**
     * @param \AutoDeploy\Service\Vcs\Service $service
     */
    public function setVcsService(\AutoDeploy\Service\Vcs\Service $service);

    /**
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager);

    /**
     * @return ServiceManager
     */
    public function getServiceManager();

    /**
     * @return \AutoDeploy\Service\Vcs\Service $service
     */
    public function getVcsService();
}