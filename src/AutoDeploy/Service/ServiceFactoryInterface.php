<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

interface ServiceFactoryInterface
{
    /**
     * @param $config
     * @param null $defaultType
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function factory($config, $defaultType = null);
}