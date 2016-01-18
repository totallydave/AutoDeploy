<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service;

interface ServiceInterface
{
    /**
     * @param array $config
     */
    public function parseConfig(array $config = []);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     *
     * @return array
     */
    public function setConfig(array $config = []);

    /**
     * @param $type
     */
    public function setType($type);

    /**
     * @return String
     */
    public function getType();

    /**
     * @param string
     */
    public function setLog($log = '');

    /**
     * @return String
     */
    public function getLog();

    /**
     * @return void
     */
    public function execute();

    /**
     * @return void
     */
    public function run();

    /**
     * @return void
     */
    public function postRun();

    /**
     * @param boolean $boolean
     *
     * @return void
     */
    public function setHasRun($boolean = false);
}