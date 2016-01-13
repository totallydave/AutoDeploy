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
use AutoDeploy\Service\ServiceFactoryInterface;
use AutoDeploy\Service\ServiceInterface;

abstract class ServiceFactory implements ServiceFactoryInterface
{
    /**
     * Registered service specific classes
     *
     * @var array
     */
    protected static $typeClasses = array(
        'composer'   => 'AutoDeploy\Service\Dm\Composer',
    );

    /**
     * @param $config
     * @param null $defaultType
     * @return Dm
     * @throws InvalidArgumentException
     */
    public static function factory($config, $defaultType = null)
    {
        if (!is_array($config)) {
            throw new InvalidArgumentException(sprintf(
                'Expecting an array, received "%s"',
                (is_object($config) ? get_class($config) : gettype($config))
            ));
        }

        $service = new Service($config);

        $type = strtolower($service->getType());
        if (!$type && $defaultType) {
            $type = $defaultType;
        }

        if ($type && !isset(static::$typeClasses[$type])) {
            throw new InvalidArgumentException(sprintf(
                'no class registered for type "%s"',
                $type
            ));
        }

        if ($type && isset(static::$typeClasses[$type])) {
            $class = static::$typeClasses[$type];
            $service = new $class($service);
            if (!$service instanceof ServiceInterface) {
                throw new InvalidArgumentException(sprintf(
                    'class "%s" registered for type "%s" does not implement AutoDeploy\Service\ServiceInterface',
                    $class,
                    $type
                ));
            }
        }

        return $service;
    }
}