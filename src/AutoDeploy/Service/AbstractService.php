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

abstract class AbstractService implements ServiceInterface
{
    /**
     * @var String
     */
    protected $log;

    /**
     * @var Array
     */
    protected $config;

    /**
     * @var String
     */
    protected $type;

    public function __construct($service)
    {
        if (is_array($service)) {
            $this->parseConfig($service);
        } elseif ($service instanceof ServiceInterface) {
            // Copy constructor
            $this->setType($service->getType());
            $this->setConfig($service->getConfig());
        } elseif ($service !== null) {
            throw new InvalidArgumentException(sprintf(
                'Expecting an array or an instance of ServiceInterface, received "%s"',
                (is_object($service) ? get_class($service) : gettype($service))
            ));
        }
    }

    /**
     * @param array $config
     */
    public function parseConfig(array $config = [])
    {
        $this->config = $config;
        $this->type = $config['type'];
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function setConfig(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return String
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->execute();
        $this->postRun();
    }

    /**
     * This is intended to be overridden by service specific execute method
     *
     * @return void
     */
    public function execute() {}

    /**
     * This is intended to be overridden by service specific post run method
     *
     * @return void
     */
    public function postRun() {}
}