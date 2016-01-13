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
use AutoDeploy\Service\ServiceInterface;

class Service implements ServiceInterface
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

    public function __construct($Dm)
    {
        if (is_array($Dm)) {
            $this->parseConfig($Dm);
        } elseif ($Dm instanceof ServiceInterface) {
            // Copy constructor
            $this->setType($Dm->getType());
            $this->setConfig($Dm->getConfig());
        } elseif ($Dm !== null) {
            throw new InvalidArgumentException(sprintf(
                'Expecting an array or a Service object, received "%s"',
                (is_object($Dm) ? get_class($Dm) : gettype($Dm))
            ));
        }

    }

    /**
     * @param $config
     */
    public function parseConfig($config)
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
     * @return array
     */
    public function setConfig($config)
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
    public function run() {}
}