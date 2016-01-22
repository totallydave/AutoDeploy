<?php
/**
 * @package   AutoDeploy
 *
 * @copyright 2014 Totally Communications (http://www.totallycommunications.com)
 * @license   http://www.totallycommunications.com/license/bsd.txt New BSD License
 * @version   $Id:$
 */
namespace AutoDeploy\Application;

use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\Sendmail as SendmailTransport;

class SystemEmail implements SystemEmailInterface
{
    public $fromName = '';
    public $fromEmail = '';
    public $siteUrl = '';
    public $siteName = '';
    public $adminUrl = '';
    public $adminName = '';

    public $ccEmail;
    public $bccEmail;
    public $replyTo;

    protected $allowedDevEmailDomains = array();

    var $patterns = array();
    var $replacements = array();

    public function __construct(array $emailConfig = array())
    {
        foreach ($emailConfig as $field => $value) {
            $this->{$field} = $value;
        }
    }

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
        $fromEmail=null,
        $fromName=null,
        $attachment=null
    ) {
        // get from email address
        $fromEmail = ($fromEmail) ? $fromEmail : $this->fromEmail;
        $fromName  = ($fromName) ? $fromName : $this->fromName;
        $from       = $fromName . '<' . $fromEmail . '>';

        // do replacements in subject
        $subject = preg_replace($this->patterns, $this->replacements, $subject);

        $message = new Message();
        $message->addFrom($fromEmail, $fromName)
            ->setSubject($subject);

        if (is_array($toEmail)) {
            foreach ($toEmail as $email) {
                $this->addEmail($message, $email);
            }
        } else {
            $this->addEmail($message, $toEmail);
        }

        $addressList = $message->getTo();
        if (!count($addressList)) {
            return false;
        }

        if (is_array($this->ccEmail)) {
            foreach ($this->ccEmail as $email) {
                $message->addCc($email);
            }
        } else if ($this->ccEmail) {
            $message->addCc($this->ccEmail);
        }

        if (is_array($this->bccEmail)) {
            foreach ($this->bccEmail as $email) {
                $message->addBcc($email);
            }
        } else if ($this->bccEmail) {
            $message->addBcc($this->bccEmail);
        }

        // define body
        // make text email content
        $content = nl2br($content);
        $textContent = strip_tags($content);
        $textPart = new MimePart($textContent);
        $textPart->type = "text/plain";

        // make HTML email content
        $htmlContent = $this->htmlContent($content);
        $htmlPart = new MimePart($htmlContent);
        $htmlPart->type = "text/html";

        $body = new MimeMessage();
        $body->setParts(array($textPart, $htmlPart));

        $message->setBody($body);
        $message->setEncoding('UTF-8');
        $message->getHeaders()
            ->get('content-type')
            ->setType('multipart/alternative');


        $transport = new SendmailTransport('-f' . $this->replyTo);

        $transport->send($message);

        return true;
    }

    /**
     * Returns HTML content of email
     *
     * @access public
     * @param string $content Content of email
     * @return string HTML content
     */
    protected function htmlContent($content)
    {
        // do replacements in content
        $content = preg_replace($this->patterns, $this->replacements, $content);

        // make HTML email content
        $htmlContent = $this->html_header();
        $htmlContent.= $content;
        $htmlContent.= $this->html_footer();

        return $htmlContent;
    }

    /**
     * Returns header part of email template
     *
     * @access private
     * @return string HTML content
     */
    protected function html_header()
    {
        $html = '
            <html>
            <body bgcolor="#FFFFFF" style="margin:0;padding:0">
                <style type="text/css" title="text/css">
                    body { font-family: Arial,"DejaVu Sans","Liberation Sans",Freesans,sans-serif; font-size: 13px; color: #000; line-height: 22px; }
                    a { color: #CC0000; text-decoration: none; }
                    /* Hack hotmail */
                    .ReadMsgBody {width: 100%;}
                    .ExternalClass {width: 100%;}
                </style>
                <center>
                    <table cellspacing="0" bgcolor="#FFFFFF" cellpadding="0" width="100%" border="0" align="center">
                        <tr>
                            <td valign="top" align="center">
                                <table cellspacing="0" cellpadding="0" width="650" border="0" align="center">
                                    <tr>
                                        <td width="650" valign="middle">
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <table cellspacing="0" cellpadding="0" width="100%" border="0" align="center" bgcolor="#FFFFFF">
                        <tr>
                            <td valign="top" align="center">
                                <table align="center" cellpadding="0" cellspacing="0" border="0" width="650" bgcolor="#FFFFFF">
                                    <tr>
                                        <td style="padding: 30px 15px; text-align: left; font-family: Arial,\'DejaVu Sans\',\'Liberation Sans\',Freesans,sans-serif; font-size: 13px; color: #000; line-height: 22px;">';


        return $html;
    }

    /**
     * Returns footer part of email template
     *
     * @access private
     * @return string HTML content
     */
    protected function html_footer()
    {

        $html = '
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <table cellspacing="0" cellpadding="0" width="650" border="0" align="center">
                                    <tr>
                                        <td style="padding: 30px 5px;font-family: Arial,\'DejaVu Sans\',\'Liberation Sans\',Freesans,sans-serif; font-size: 13px; color: #000; line-height: 22px;" align="center">
                                            <p>

                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </center>
            </body>
            </html>';

        return $html;
    }

    /**
     * Proxy method to $message->addTo($email). Make sure, if we're on dev
     * environment that only authorises email addresses are added.
     *
     * @param \Zend\Mail\Message $message
     * @param string $email
     *
     * @return void
     */
    protected function addEmail($message, $email)
    {
        if (getenv('env') === 'dev') {
            // only authorise TC email's address
            foreach ($this->allowedDevEmailDomains as $domain) {
                if (preg_match('/' . $domain . '$/', $email)) {
                    $message->addTo($email);
                }
            }
        } else {
            $message->addTo($email);
        }
    }
}
