<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

use AutoDeploy\Exception\InvalidArgumentException;
use AutoDeploy\Application\Log;

class ServiceManager implements ServiceManagerInterface
{
    const SERVICE_TYPE_VCS = 'vcs';
    const SERVICE_TYPE_DM = 'dm';
    const SERVICE_TYPE_DB = 'db';

    /**
     * @var array
     */
    protected static $serviceNamespaces = array(
        self::SERVICE_TYPE_VCS => 'AutoDeploy\Service\Vcs',
        self::SERVICE_TYPE_DM => 'AutoDeploy\Service\Dm',
        self::SERVICE_TYPE_DB => 'AutoDeploy\Service\Db',
    );

    /**
     * @var array
     */
    protected $services = array();

    /**
     * @var Log
     */
    protected $log;

    public function __construct(array $config = array(), Log $log)
    {
        if (!array_key_exists('services', $config)) {
            throw new InvalidArgumentException("'services' config not found");
        }

        // lets check we have registered namespaces for all the provided services
        foreach ($config['services'] as $serviceName => $serviceConfig) {
            if (!isset(static::$serviceNamespaces[$serviceName])) {
                throw new InvalidArgumentException(sprintf(
                    "'%s' not found in registered serviceNamespaces",
                    $serviceName
                ));
            }

            if (!class_exists(static::$serviceNamespaces[$serviceName] . '\ServiceFactory')) {
                throw new InvalidArgumentException(sprintf(
                    "ServiceFactory not found in registered service namespace '%s'",
                    static::$serviceNamespaces[$serviceName]
                ));
            }

            /**
             *  does the factory method exist
             *
             * @todo potentially change to verify that the class is an instance of the ServiceFactoryInterface
             */
            if (!is_callable(static::$serviceNamespaces[$serviceName] . '\ServiceFactory::factory')) {
                throw new InvalidArgumentException(sprintf(
                    "factory method not found in registered service class '%s'",
                    static::$serviceNamespaces[$serviceName] . '\ServiceFactory'
                ));
            }

            // we want to force the db service to be the last this updated
            if ($serviceName === static::SERVICE_TYPE_DB) {
                $databaseService = call_user_func_array(
                    array(static::$serviceNamespaces[$serviceName] . '\ServiceFactory', 'factory'), array($serviceConfig)
                );
            } else {
                $this->services[$serviceName] = call_user_func_array(
                    array(static::$serviceNamespaces[$serviceName] . '\ServiceFactory', 'factory'), array($serviceConfig)
                );
            }
        }

        // do we have a database service
        if (isset($databaseService)) {
            // add the database service to the end of the service queue
            $this->services[] = $databaseService;
        }

        // set the vcs service so we can use it later
        foreach ($this->services as $serviceName => $service) {
            $service->setServiceManager($this);

            if ($serviceName === static::SERVICE_TYPE_VCS) {
                continue;
            }

            $service->setVcsService($this->services[static::SERVICE_TYPE_VCS]);
        }

        $this->log = $log;
    }

    /**
     * @param string $serviceName
     *
     * @return ServiceInterface
     */
    public function getService($serviceName = null)
    {
        if (!array_key_exists($serviceName, $this->services)) {
            return null;
        }

        return $this->services[$serviceName];
    }

    /**
     * @return void
     */
    public function run()
    {
        foreach ($this->services as $service) {
            $service->run();
        }

        $this->generateLog();
    }

    /**
     * @return void
     */
    public function rollBack()
    {
        foreach ($this->services as $service) {
            $service->rollBack();
        }
    }

    /**
     * @return void
     */
    protected function generateLog()
    {
        $summary = array(
            'successful' => array(),
            'failed' => array(),
            'rolledBack' => array(),
        );

        $log = $this->getLog();
        foreach ($this->services as $service) {

            $log->addMessage('------------------------ ' . $service->getType() . ' start')
                ->addLog($service->getLog())
                ->addMessage('------------------------ ' . $service->getType() . ' end');

            if ($service->getHasRun()) {
                $summary['successful'][] = $service->getType();
            } else {
                $summary['failed'][] = $service->getType();
            }

            if ($service->getHasRolledBack()) {
                $summary['rolledBack'][] = $service->getType();
            }
        }

        $log->addMessage('------------------------ AutoDeploy summary')
            ->addMessage("Successful services: \n" . implode("\n", $summary['successful']))
            ->addMessage("Failed services: \n" . implode("\n", $summary['failed']));

        if (count($summary['failed'])) {
            $log->addMessage('Rolled Back services: \n"' . implode("\n", $summary['rolledBack']));
        }
    }

    /**
     * @return Log
     */
    public function getLog()
    {
        return $this->log;
    }
}