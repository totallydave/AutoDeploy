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

class Service extends AbstractService implements DbServiceInterface
{
    /**
     * @var \AutoDeploy\Service\Vcs\Service
     */
    protected $vcsService;

    /**
     * @var array
     */
    protected $updatedFiles = [];

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
    }

    /**
     * @param boolean $relativeToRoot
     *
     * @return array
     */
    protected function getMigrationDirFiles($relativeToRoot = false)
    {
        $config = $this->getConfig();

        $files = scandir($config['migrationDir']);
        if (!$relativeToRoot) {
            return $files;
        }

        $migrationDirectory = (preg_match("/\\" . DIRECTORY_SEPARATOR . "$/", $config['migrationDir'])) ?
                            $config['migrationDir'] :
                            $config['migrationDir'] . DIRECTORY_SEPARATOR;

        $dirFiles = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' ||
                // we only want the files with the correct naming convention
                !preg_match("/^_auto_deploy_/", $file)) {
                continue;
            }

            $dirFiles[] = $migrationDirectory . $file;
        }

        return $dirFiles;
    }

    /**
     * @param \AutoDeploy\Service\Vcs\Service $service
     */
    public function setVcsService(\AutoDeploy\Service\Vcs\Service $service)
    {
        $this->vcsService = $service;
    }

    /**
     * @return \AutoDeploy\Service\Vcs\Service $service
     */
    public function getVcsService()
    {
        return $this->vcsService;
    }

    /**
     * @return boolean
     */
    protected function hasVcsUpdated()
    {
        return $this->getVcsService()->hasUpdated();
    }

        /**
     * @return boolean
     */
    protected function isDbServiceUpdateRequired()
    {
        $updatedFiles = [];
        $migrationDirectoryFiles = $this->getMigrationDirFiles(true);
        $migrationDirectoryFilesFlipped = array_flip($migrationDirectoryFiles);

        foreach ($this->getVcsService()->getUpdatedFiles() as $file) {
            if (array_key_exists($file, $migrationDirectoryFilesFlipped)) {
                $updatedFiles[] = $file;
            }
        }

        $this->updatedFiles = $updatedFiles;

        return (count($updatedFiles) > 0) ? true : false;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $log = $this->getLog() . "\n";

        // is there a vcs change?
        if (!$this->hasVcsUpdated()) {
            $log .= $this->getVcsService()->getType() . ' has not been updated so no new db migration files';

            $this->setLog($log);
            // nothing to do here
            return;
        }

        if (!$this->isDbServiceUpdateRequired()) {
            $log .= sprintf(
                'There are no new db migration files in "%s"',
                $this->getConfig()['migrationDir']
            );

            $this->setLog($log);

            return;
        }

        $this->executeBackup();
        $this->executeMigration();
    }

    /**
     * @return void
     */
    public function executeMigration() {}

    /**
     * @return void
     */
    public function executeBackup() {}
}