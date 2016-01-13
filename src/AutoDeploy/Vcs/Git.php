<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Vcs;

use AutoDeploy\Exception\InvalidArgumentException;

class Git extends Vcs implements VcsInterface
{
    /**
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function run()
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
        exec("git reset --hard", $gitReset);

        // do git pull
        exec("git pull", $gitPull);

        $log = "\nResult of git pull:\n"
             . implode("\n", $gitReset) . "\n"
             . implode("\n", $gitPull) . "\n";

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
}