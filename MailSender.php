<?php
namespace Theapi\CctvBundle;

/**
 *
 * @see http://swiftmailer.org/docs
 * @author theapi
 *
 * @TODO Use SwiftmailerBundle
 *
 */


/**
 * Sends email
 */
class MailSender
{

  /**
   * The swiftmailer object
   */
  protected $mailer;

  /**
   * The configuration array
   */
  protected $config;

  /**
   * Constructor
   *
   */
  public function __construct($mailer, $config) {
    $this->mailer = $mailer;
    $this->config = $config;
  }

  public function setProcess($process) {
    $this->process = $process;
  }

  public function sendMail($subject, $body, $filePath = null, $viaSpool = false) {

    $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom($this->config['from'])
        ->setTo($this->config['to'])
        ->setBody($body)
    ;

    if (!empty($filePath)) {
        $message->attach(\Swift_Attachment::fromPath($filePath));
    }

    return $this->mailer->send($message);
  }

  public function sendTestMail($viaSpool = false) {

    // Create a message
    if ($viaSpool) {
      $body = 'This message was sent from the spool.';
    } else {
      $body = 'This message was sent directly via SMTP.';
    }

    $subject = 'Test from RobocopBundle via MailSender';
    $filePath = __DIR__ . '/Resources/public/images/peter.jpg';

    return $this->sendMail($subject, $body, $filePath, $viaSpool);
  }

}
