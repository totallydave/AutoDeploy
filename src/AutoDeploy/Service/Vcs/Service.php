<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Vcs;

use AutoDeploy\Service\AbstractService;
use AutoDeploy\Application\Log;

class Service extends AbstractService implements VcsServiceInterface
{
    /**
     * @var string
     */
    protected $preRunUniqueId;

    /**
     * @var string
     */
    protected $postRunUniqueId;

    /**
     * @param $service
     * @param Log $log
     */
    public function __construct($service, Log $log)
    {
        parent::__construct($service, $log);

        $this->setPreRunUniqueId($this->getUniqueId());
    }

    /**
     * @param string $id
     */
    protected function setPreRunUniqueId($id = null)
    {
        $this->preRunUniqueId = $id;
    }

    /**
     * @param string $id
     */
    protected function setPostRunUniqueId($id = null)
    {
        $this->postRunUniqueId = $id;
    }

    /**
     * @return string
     */
    public function getPreRunUniqueId()
    {
        return $this->preRunUniqueId;
    }

    /**
     * @return string
     */
    public function getPostRunUniqueId()
    {
        return $this->postRunUniqueId;
    }

    /**
     * Intended to be overridden
     *
     * @return string
     */
    protected function getUniqueId() {}

    /**
     * @return void
     */
    public function postRun()
    {
        $this->setPostRunUniqueId($this->getUniqueId());
    }

    /**
     * @return boolean
     */
    public function hasUpdated()
    {
        if (!$this->getHasRun()) {
            return false;
        }

        return $this->preRunUniqueId !== $this->postRunUniqueId;
    }

    /**
     * @return array
     */
    public function getUpdatedFiles()
    {
        return array();
    }

    /**
     * @return string
     */
    public function findProjectRoot()
    {
        return APPLICATION_ROOT;
    }
}