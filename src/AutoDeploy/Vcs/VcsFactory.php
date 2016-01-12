<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Vcs;

abstract class VcsFactory
{
    /**
     * Registered vcs-specific classes
     *
     * @var array
     */
    protected static $typeClasses = array(
        'git'   => 'AutoDeploy\Vcs\Git',
    );

    /**
     * @param $config
     * @param null $defaultType
     * @return Vcs
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($config, $defaultType = null)
    {
        if (!is_array($config)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting an array, received "%s"',
                (is_object($config) ? get_class($config) : gettype($config))
            ));
        }

        $vcs = new Vcs($config);

        $type = strtolower($vcs->getType());
        if (!$type && $defaultType) {
            $type = $defaultType;
        }

        if ($type && !isset(static::$typeClasses[$type])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'no class registered for type "%s"',
                $type
            ));
        }

        if ($type && isset(static::$typeClasses[$type])) {
            $class = static::$typeClasses[$type];
            $vcs = new $class($vcs);
            if (!$vcs instanceof VcsInterface) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'class "%s" registered for type "%s" does not implement AutoDeploy\Vcs\VcsInterface',
                    $class,
                    $type
                ));
            }
        }

        return $vcs;
    }
}