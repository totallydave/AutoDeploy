<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

interface ServiceManagerInterface
{
    /**
     * @return void
     */
    public function run();

    /**
     * @return string
     */
    public function getLog();
}