<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Dm;

use AutoDeploy\Exception\InvalidArgumentException;
use AutoDeploy\Exception\RuntimeException;
use Zend\Json\Json;

class Composer extends Service
{
    /**
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        // get project root
        $projectRoot = $this->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        // swap to project root
        chdir($projectRoot);

        // update composer
        ob_clean();
        ob_start();
        system("composer update --no-dev 2>&1");
        $composerUpdate = ob_get_clean();

        $log = "\nResult of composer update:\n";
        if (is_array($composerUpdate)) {
            $log .= implode("\n", $composerUpdate) . "\n";
        } elseif (is_string($composerUpdate)) {
            $log .= $composerUpdate . "\n";
        }

        $this->log = $log;
    }

    /**
     * Find root of project
     *
     * Starts in current folder and recursively works upwards looking
     * for .git/config
     *
     * @return string
     */
    protected function findProjectRoot()
    {
        $config = $this->getConfig();

        chdir(dirname(__DIR__));
        $dir = realpath(dirname(__DIR__));

        while ($dir) {
            $composerConfig = $dir . DIRECTORY_SEPARATOR
                            . 'composer.json';

            if (file_exists($composerConfig)) {
                $composerConfig = Json::decode(file_get_contents($composerConfig));

                if (!empty($composerConfig->name) && $composerConfig->name == $config['name']) {
                    echo "found composer.json at $dir";

                    break;
                }
            }

            // lets move up a level
            $dir = realpath($dir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
            chdir($dir);
        }

        return $dir;
    }

    /**
     * @throws RuntimeException
     */
    public function executeRollback()
    {
        // get project root
        $projectRoot = $this->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        if (!$this->getVcsService()->getHasRolledBack()) {
            throw new RuntimeException(
                "Rolling back composer depends on VCS having rolled back successfully"
            );
        }

        // swap to project root
        chdir($projectRoot);

        // update composer
        ob_clean();
        ob_start();
        system("composer update --no-dev 2>&1", $returnValue);
        $composerUpdate = ob_get_clean();

        if (!empty($returnValue)) {
            throw new RuntimeException(
                "Issue rolling back composer : " . $composerUpdate
            );
        }

        $this->setLog($this->getLog() . "\nComposer Rollback: " . $composerUpdate);
    }
}