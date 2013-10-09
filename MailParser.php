<?php
namespace Theapi\CctvBundle;

use MimeMailParser\Parser;
use MimeMailParser\Attachment;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class MailParser extends ContainerAware
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
   * Saved files that were attached in the email.
   *
   * @var array
   */
  protected $files = array();

      /**
     * The event dispatcher
     */
    protected $eventDispatcher;

  /**
   * Constructor
   *
   */
  public function __construct($saveDir) {
    $this->saveDir = $saveDir;
    $this->parser = new Parser();
  }

    public function setMailerSender($mailSender)
    {
        $this->mailSender = $mailSender;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->addListeners();
    }

    public function addListeners()
    {
        $this->eventDispatcher = $this->container->get('event_dispatcher');
        $this->eventDispatcher->addListener('blindfold.open', array($this, 'blindfoldListener'));
        $this->eventDispatcher->addListener('blindfold.close', array($this, 'blindfoldListener'));
    }

    public function blindfoldListener()
    {
        // Remember when it was moved so as not to forward email about it opening.
        touch('/tmp/cctv_bf_moved');
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

    // Perfom actions depending on which channel the image came from.
    //@todo: make channel actions configurable.
    //@todo: make channel actions plugins
    $channelActions = array('CH02' => array('blindfoldMotion'));

    foreach ($this->files as $filename) {
      $channel = $this->getChannelFromFilename($filename);
      if (isset($channelActions[$channel])) {
        foreach ($channelActions[$channel] as $action) {
          $this->$action();
        }
      }
    }

  }

  protected function blindfoldMotion()
  {
      $now = time();
      if (file_exists('/tmp/cctv_bf_moved')) {
          $modified = filemtime('/tmp/cctv_bf_moved');
      }

      if (!isset($modified) || ($now - $modified > 180)) {
          $this->passOnMessage();
      }
  }

  protected function passOnMessage() {
    if (!empty($this->mailSender)) {
      $subject = '(Robocop) ' . $this->parser->getHeader('subject');
      $body = $this->parser->getMessageBody('text');
      $sent = $this->mailSender->sendMail($subject, $body, $this->files);
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
          $this->files[] = $dir . '/' . $filename;
        }
      }
      return $attachments;
    }
    return false;
  }

}
