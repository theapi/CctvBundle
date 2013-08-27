<?php
namespace Theapi\CctvBundle;

use MimeMailParser\Parser;
use MimeMailParser\Attachment;

/**
 *
 * @see http://pecl.php.net/package/mailparse
 * @see http://php-mime-mail-parser.googlecode.com
 * @see https://packagist.org/packages/messaged/php-mime-mail-parser
 * @author theapi
 *
 */

/**
 * Parse email
 */
class MailParser
{

  /**
   * The MimeMailParser object
   */
  protected $parser;

  /**
   * Where the images get saved
   */
  protected $saveDir;

  /**
   * Mail sending object
   */
  protected $mailSender;

  /**
   * Constructor
   *
   */
  public function __construct($saveDir) {
    $this->saveDir = $saveDir;
    $this->parser = new Parser();
  }

  public function processIncomingMail() {
    $this->parser->setStream(STDIN);
    $subject = $this->parser->getHeader('subject');
    switch ($subject) {
      // TODO: Make incoming mail subject switch case configurable
      case 'from your dvr\'s snap jpg':
        $this->processSnaps();
        break;
      default:
        $this->passOnMessage();
        break;
    }
  }

  /**
   * Grab som incoming mail for using as test mails.
   */
  public function storeIncomingMail() {
    $contents = stream_get_contents(STDIN);
    error_log(print_r($contents, 1), 3, $this->saveDir . "/incoming_mail.txt");
  }

  public function setMailerSender($mailSender) {
    $this->mailSender = $mailSender;
  }

  /**
   * Parses the filename to get the camera channel the image is from.
   */
  public function getChannelFromFilename($filename) {
    if (preg_match('/(CH\d+)_/', $filename, $matches)) {
      return $matches[1];
    }
    return 'CH00';
  }

  protected function processSnaps() {
    if (!$this->saveAttachments()) {
      // no attachments
      return;
    }

    // When was the last email sent?

    // If not too recent, attach the images that have not yet been sent

  }

  protected function passOnMessage() {
    if (!empty($this->mailSender)) {
      $subject = '(Robocop) ' . $this->parser->getHeader('subject');
      $body = $this->parser->getMessageBody('text');
      $sent = $this->mailSender->sendMail($subject, $body, null);
    }
  }

  protected function saveAttachments() {
    $attachments = $this->parser->getAttachments();
    if (is_array($attachments) && count($attachments) > 0 ) {
      $dir = $this->saveDir . '/in_' . date('Y-m-d');
      foreach($attachments as $attachment) {
        // get the attachment name
        $filename = $attachment->filename;

        // Separate directory per channel
        $channel = $this->getChannelFromFilename($filename);
        $dir .= '/' . $channel;

        // write the file to the directory you want to save it in
        @mkdir($dir, 0777, true);
        if ($fp = fopen($dir . '/' . $filename, 'w')) {
          while($bytes = $attachment->read()) {
            fwrite($fp, $bytes);
          }
          fclose($fp);
        }
      }
      return $attachments;
    }
    return false;
  }

}
