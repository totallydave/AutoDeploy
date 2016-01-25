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
use AutoDeploy\Application\Log;

class Service extends AbstractService implements DbServiceInterface
{
    /**
     * @var array
     */
    protected $updatedFiles = array();

    public function __construct($service, Log $log)
    {
        parent::__construct($service, $log);

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

        $dirFiles = array();
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
     * @return boolean
     */
    protected function isDbServiceUpdateRequired()
    {
        $updatedFiles = array();
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
     *
     * @throws \Exception
     */
    public function execute()
    {
        // is there a vcs change?
        if (!$this->hasVcsUpdated()) {
            $message = $this->getVcsService()->getType()
                     . " has not been updated so no new db migration files";

            $this->getLog()->addMessage(
                $message
            );
            // nothing to do here
            return;
        }

        if (!$this->isDbServiceUpdateRequired()) {
            $config = $this->getConfig();
            $message = sprintf(
                'There are no new db migration files in "%s"' . "\n",
                $config['migrationDir']
            );

            $this->getLog()->addMessage(
                $message
            );
            return;
        }

        try {
            $this->executeBackup();
            $this->executeMigration();
        } catch (\Exception $e) {
            $message = "---------------ERROR---------------\n"
                     . $e->getMessage();

            $this->getLog()->addMessage(
                $message
            );

            // this will get caught in the controller if exception is thrown
            $this->getServiceManager()->rollBack();

            throw $e;
        }
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