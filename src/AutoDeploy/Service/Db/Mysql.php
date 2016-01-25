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
use AutoDeploy\Exception\RuntimeException;

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

        $log = $this->getLog() . "\n";
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

        $success = true;
        foreach ($this->updatedFiles as $file) {
            $log .= "Executing file '" . $file . "'\n";

            $sqlStringToExecute = preg_replace("/\[FILE\]/", "$file", $sql);

            ob_clean();
            ob_start();
            system($sqlStringToExecute, $return);
            $result = ob_get_clean();

            if (empty($return)) {
                $result = 'Success';
            } else {
                $success = false;
            }

            $log .= "Result of mysql update: \n";
            if (is_array($result)) {
                $log .= implode("\n", $result) . "\n";
            } elseif (is_string($result)) {
                $log .= $result . "\n";
            }
        }

        $this->setLog($log);

        if (!$success) {
            throw new RuntimeException("Mysql import was unsuccessful : " . $this->getLog());
        }
    }

    /**
     * @return void
     */
    public function executeBackup()
    {
        // get project root
        $projectRoot = $this->getVcsService()->findProjectRoot();
        if (!$projectRoot) {
            $message = 'Could not determine project root directory';
            throw new InvalidArgumentException($message);
        }

        // swap to project root
        chdir($projectRoot);

        $config = $this->getConfig();
        $connections = $config['backup_connections'];

        if (empty($connections)) {
            throw new InvalidArgumentException(
                "Back up config must be defined to perform migration"
            );
        }

        if (!is_array($connections)) {
            throw new InvalidArgumentException(sprintf(
                "Expected array for 'backup_connections' received '%s'",
                gettype($connections)
            ));
        }

        if (!is_dir($config['backupDir'])) {
            throw new InvalidArgumentException(sprintf(
                "'%s' is not a directory",
                $config['backupDir']
            ));
        }

        $backupDir = (preg_match("/\\" . DIRECTORY_SEPARATOR . "$/", $config['backupDir'])) ?
                    $config['backupDir'] :
                    $config['backupDir'] . DIRECTORY_SEPARATOR;

        // lets get the pre and post run unique id from vcs so the backup files can be identified
        $backupDumpFileId = $this->getVcsService()->getPreRunUniqueId()
                          . '-' . $this->getVcsService()->getPostRunUniqueId()
                          . '-' . time();

        $success = true;
        foreach ($connections as $backupConfig) {
            $host = $backupConfig['hostname'];
            $username = $backupConfig['username'];
            $password = $backupConfig['password'];
            $database = $backupConfig['database'];

            $dumpFile = $backupDir . $backupConfig['database']
                      . '_' . $backupDumpFileId . '.sql';

            $this->getLog()->addMessage(
                "Creating dump file '$dumpFile' for database '$database'"
            );

            $sql = "mysqldump -u$username -h$host -p$password $database > $dumpFile 2>&1";

            ob_clean();
            ob_start();
            system($sql, $return);
            $result = ob_get_clean();

            if (file_exists($dumpFile) && empty($return)) {
                $result = 'Success';
            } else {
                $success = false;
            }

            $message = "Result of mysql dump: \n";
            if (is_array($result)) {
                $message .= implode("\n", $result) . "\n";
            } elseif (is_string($result)) {
                $message .= $result . "\n";
            }

            $this->getLog()->addMessage(
                $message
            );
        }

        if (!$success) {
            throw new RuntimeException("Mysql dump was unsuccessful : " . $this->getLog());
        }
    }

    /**
     * @return void
     */
    public function executeRollback()
    {
        throw new RuntimeException(
            "Mysql rollback not written yet - Please do so manually"
        );
    }
}