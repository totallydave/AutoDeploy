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

abstract class AbstractServiceFactory implements ServiceFactoryInterface
{
    /**
     * Registered service specific classes
     *
     * @var array
     */
    protected static $typeClasses = array();

    /**
     * @param $config
     * @param null $defaultType
     * @return Vcs
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

        // late static binding
        $serviceClassName = '\\' . substr(get_called_class(), 0, strrpos(get_called_class(), '\\'))
                          . '\Service';

        $service = new $serviceClassName($config);

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