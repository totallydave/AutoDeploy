<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Vcs;

use AutoDeploy\Exception\InvalidArgumentException;

class Vcs implements VcsInterface
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

    public function __construct($vcs)
    {
        if (is_array($vcs)) {
            $this->parseConfig($vcs);
        } elseif ($vcs instanceof VcsInterface) {
            // Copy constructor
            $this->setType($vcs->getType());
            $this->setConfig($vcs->getConfig());
        } elseif ($vcs !== null) {
            throw new InvalidArgumentException(sprintf(
                'Expecting an array or a Vcs object, received "%s"',
                (is_object($vcs) ? get_class($vcs) : gettype($vcs))
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