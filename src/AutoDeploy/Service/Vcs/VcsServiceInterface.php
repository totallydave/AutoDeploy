<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Service\Vcs;

interface VcsServiceInterface
{
    /**
     * @return boolean
     */
    public function hasUpdated();

    /**
     * @return array
     */
    public function getUpdatedFiles();

    /**
     * @return string
     */
    public function findProjectRoot();

    /**
     * @return string
     */
    public function getPreRunUniqueId();

    /**
     * @return string
     */
    public function getPostRunUniqueId();

    /**
     * @return bool
     */
    public function getHasRolledBack();
}