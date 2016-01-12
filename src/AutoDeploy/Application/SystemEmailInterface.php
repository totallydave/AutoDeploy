<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Application;

interface SystemEmailInterface
{
    /**
     * Send email
     *
     * @access public
     * @param string $toEmail Email address to send email to
     * @param string $subject Subject of email
     * @param string $content Content of email
     * @param string $fromEmail Email address email should come from,
     *               if not supplied will use system default
     * @param string $fromName Name to appear in from email address,
     *               if not supplied will use system default
     * @return void
     */
    public function send(
        $toEmail,
        $subject,
        $content,
        $fromEmail = null,
        $fromName = null,
        $attachment = null
    );
}
