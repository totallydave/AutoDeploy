<?php
/**
 * Created by PhpStorm.
 * User: kingd
 * Date: 25/01/16
 * Time: 11:59
 */

namespace AutoDeploy\Application;

use AutoDeploy\Exception\InvalidArgumentException;

class Log
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getOutputString();
    }

    /**
     * @param string $line
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function addMessage($line = '')
    {
        if (!is_string($line)) {
            throw new InvalidArgumentException(sprintf(
               "Expected string and received '%s'",
                gettype($line)
            ));
        }

        $this->messages[] = $line;

        return $this;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function addLog(Log $log)
    {
        $this->messages = array_merge($this->messages, $log->getMessages());

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return string
     */
    public function getOutputString()
    {
        return implode("\n", $this->messages);
    }
}