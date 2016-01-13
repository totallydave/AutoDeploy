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
        'vcs' => 'AutoDeploy\Service\Vcs'
    ];

    /**
     * @var array
     */
    protected $services = [];

    public function __contruct(array $config = [])
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

            $this->services[] = call_user_func_array(
                static::$serviceNamespaces[$serviceName] . '\ServiceFactory', [$config]
            );
        }
    }

    public function run()
    {
        var_dump($this);die;
    }
}