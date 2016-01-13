<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Db;

use AutoDeploy\Service\AbstractService;
use AutoDeploy\Exception\InvalidArgumentException;

class Service extends AbstractService
{
    /**
     * @var \AutoDeploy\Service\Vcs\Service
     */
    protected $vcsService;

    public function __construct($service)
    {
        parent::__construct($service);

        $config = $this->getConfig();

        if (empty($config['migrationDir'])) {
            throw new InvalidArgumentException(
                "'migrationDir' config must be set"
            );
        }

        if (!is_string($config['migrationDir'])) {
            throw new InvalidArgumentException(sprintf(
                'Expecting a string, received "%s"',
                (is_object($config['migrationDir']) ? get_class($config['migrationDir']) : gettype($config['migrationDir']))
            ));
        }

        // is the supplied config a directory ?
        if (!is_dir($config['migrationDir'])) {
            throw new InvalidArgumentException(sprintf(
                'Expecting a directory, received "%s"',
                $config['migrationDir']
            ));
        }
    }

    /**
     * @return array
     */
    protected function getMigrationFiles()
    {
        $config = $this->getConfig();
        return scandir($config['migrationDir']);
    }

    /**
     * @param \AutoDeploy\Service\Vcs\Service $service
     */
    public function setVcsService(\AutoDeploy\Service\Vcs\Service $service)
    {
        $this->vcsService = $service;
    }

    /**
     *
     */
    public function run()
    {
        $this->postRun();
    }

    /**
     *
     */
    protected function postRun()
    {
        $this->setPostRunUniqueId($this->getUniqueId());
    }
}