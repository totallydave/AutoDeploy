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

class ServiceManager implements ServiceManagerInterface
{
    const SERVICE_TYPE_VCS = 'vcs';
    const SERVICE_TYPE_DM = 'dm';
    const SERVICE_TYPE_DB = 'db';

    /**
     * @var array
     */
    protected static $serviceNamespaces = [
        self::SERVICE_TYPE_VCS => 'AutoDeploy\Service\Vcs',
        self::SERVICE_TYPE_DM => 'AutoDeploy\Service\Dm',
        self::SERVICE_TYPE_DB => 'AutoDeploy\Service\Db',
    ];

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var string
     */
    protected $log;

    public function __construct(array $config = [])
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
                    [static::$serviceNamespaces[$serviceName] . '\ServiceFactory', 'factory'], [$serviceConfig]
                );
            } else {
                $this->services[$serviceName] = call_user_func_array(
                    [static::$serviceNamespaces[$serviceName] . '\ServiceFactory', 'factory'], [$serviceConfig]
                );
            }
        }

        // set the vcs service so we can use it later
        $databaseService->setVcsService($this->services[static::SERVICE_TYPE_VCS]);

        // add the database service to the end of the service queue
        $this->services[] = $databaseService;
    }

    /**
     * @return void
     */
    public function run()
    {
        foreach ($this->services as $service) {
            $service->run();
        }
    }

    /**
     * @return string
     */
    public function getLog()
    {
        if ($this->log === null) {
            $log = '';

            foreach ($this->services as $service) {
                $log .= '------------------------ ' . $service->getType() . ' start';
                $log .= $service->getLog();
                $log .= '------------------------ ' . $service->getType() . ' end';
                $log .= "\n";
            }

            $this->log = $log;
        }

        return $this->log;
    }
}