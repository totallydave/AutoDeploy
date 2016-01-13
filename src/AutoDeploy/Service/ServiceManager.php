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
    /**
     * @var array
     */
    protected static $serviceNamespaces = [
        'vcs' => 'AutoDeploy\Service\Vcs',
        'dm' => 'AutoDeploy\Service\Dm',
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

            if (!is_callable(static::$serviceNamespaces[$serviceName] . '\ServiceFactory::factory')) {
                throw new InvalidArgumentException(sprintf(
                    "factory method not found in registered service class '%s'",
                    static::$serviceNamespaces[$serviceName] . '\ServiceFactory'
                ));
            }

            $this->services[] = call_user_func_array(
                [static::$serviceNamespaces[$serviceName] . '\ServiceFactory', 'factory'], [$serviceConfig]
            );
        }
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