<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Db;

use AutoDeploy\Service\AbstractServiceFactory;

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * Registered service specific classes
     *
     * @var array
     */
    protected static $typeClasses = array(
        'mysql'   => 'AutoDeploy\Service\Db\Mysql',
    );
}