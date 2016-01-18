<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Db;

use AutoDeploy\Exception\InvalidArgumentException;

class Mysql extends Service
{
    /**
     * @return void
     */
    public function executeMigration()
    {
        // get project root
        $projectRoot = $this->getVcsService()->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        // swap to project root
        chdir($projectRoot);

        $log = "\n";
        $config = $this->getConfig();
        $connection = $config['connection'];

        $host = $connection['hostname'];
        $username = $connection['username'];
        $password = $connection['password'];
        $database = $connection['database'];

        $sql = "mysql -u$username -h$host -p$password ";

        if (!empty($database)) {
            $sql .= "$database ";
        }

        $sql .= "< [FILE] 2>&1";

        foreach ($this->updatedFiles as $file) {
            $log .= "Executing file '" . $file . "'\n";

            $sqlStringToExecute = preg_replace("/\[FILE\]/", "$file", $sql);

            ob_clean();
            ob_start();
            system($sqlStringToExecute, $return);
            $result = ob_get_clean();

            if (empty($return)) {
                $result = 'Success';
            }

            $log .= "Result of mysql update: \n";
            if (is_array($result)) {
                $log .= implode("\n", $result) . "\n";
            } elseif (is_string($result)) {
                $log .= $result . "\n";
            }
        }

        $this->setLog($log);
    }
}