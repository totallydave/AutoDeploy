<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Vcs;

use AutoDeploy\Exception\InvalidArgumentException;

class Git extends Service
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

        // get current branch
        exec("git rev-parse --abbrev-ref HEAD", $currentBranch);

        $currentBranch = $currentBranch[0];

        // check current branch matches expected auto_deploy branch
        if ($currentBranch != $this->config['branch']) {
            $message = 'Current branch "' . $currentBranch . '" does not '
                . 'match excepted auto deploy branch "'
                . $this->config['branch'] . '"';

            throw new InvalidArgumentException($message);
        }

        // do git reset
        ob_clean();
        ob_start();
        system("git reset --hard 2>&1");
        $gitReset = ob_get_clean();

        // do git pull
        ob_clean();
        ob_start();
        system("git pull 2>&1");
        $gitPull = ob_get_clean();

        if (is_array($gitReset)) {
            $gitReset = implode("\n", $gitReset);
        }

        if (is_array($gitPull)) {
            $gitPull = implode("\n", $gitPull);
        }

        $log = "\nResult of git reset:\n"
            . $gitReset . "\n"
            . "\nResult of git pull:\n"
            . $gitPull . "\n";

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
    public function findProjectRoot()
    {
        $config = $this->getConfig();

        chdir(dirname(__DIR__));
        $dir = realpath(dirname(__DIR__));

        while ($dir) {
            $gitConfig = $dir . DIRECTORY_SEPARATOR
                . '.git' . DIRECTORY_SEPARATOR . 'config';

            if (file_exists($gitConfig)) {
                exec('git config --get remote.origin.url', $url);

                if (is_array($url)) {
                    $url = current($url);
                }

                if ($url == $config['originUrl']) {
                    echo "found .git/config at $dir";

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
     * @return string
     */
    protected function getUniqueId()
    {
        // get project root
        $projectRoot = $this->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        // swap to project root
        chdir($projectRoot);

        // get commit hash
        exec('git rev-parse HEAD', $commitId);

        if (is_array($commitId)) {
            $commitId = current($commitId);
        }

        return $commitId;
    }

    /**
     * @return array
     */
    public function getUpdatedFiles()
    {
        // get project root
        $projectRoot = $this->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        // swap to project root
        chdir($projectRoot);

        $gitDiff = 'git diff --name-only ' . $this->preRunUniqueId . ' ' . $this->postRunUniqueId;
        // testing below

        exec($gitDiff, $updatedFiles);

        return $updatedFiles;
    }
}