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

class Service extends AbstractService
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
     */
    public function __construct($service)
    {
        parent::__construct($service);

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
}