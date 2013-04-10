<?php
namespace Theapi\CctvBundle;

use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @see http://www.imagemagick.org/script/index.php
 * @author theapi
 *
 */

/**
 * Do stuff with images
 */
class ImageManager
{

  /**
   * The configuration array
   */
  protected $config;

  /**
   * Where the images get saved
   */
  protected $saveDir;

  /**
   * The OutputInterface object
   */
  protected $output;

  /**
   * A Symfony\Component\Process object
   */
  protected $process;

  /**
   * Constructor
   */
  public function __construct($saveDir, $config, $process) {
    $this->saveDir = $saveDir;
    $this->config = $config;
    $this->process = $process;
  }

  public function setOutput(OutputInterface $output) {
    $this->output = $output;
  }

  public function compareDateDir($date = null, $diffThreshold = null) {

    if (empty($date)) {
      // Use the directory for today
      $date = date('Y-m-d');
    } else {
      // enforce date is in the format Y-m-d
      $date = date('Y-m-d', strtotime($date));
    }

    $dir = $this->saveDir . '/in_' . $date;
    if (!is_dir($dir)) {
      throw new \Exception($dir . ' is not a directory');
    }
    $processedDir = $this->saveDir . '/p_' . $date;

    // Compare the directories for each channel
    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file != '.' && $file != '..' && is_dir($dir . '/' . $file)) {
        $sourceDir = $dir . '/' . $file;
        $destinationDir = $processedDir . '/' . $file;
        $this->compareDir($sourceDir, $destinationDir, $diffThreshold);
      }
    }

  }

  public function compareDir($dir, $destinationDir, $diffThreshold = null) {

    if (!is_dir($dir)) {
      throw new \Exception($dir . ' is not a directory');
    }

    if (empty($diffThreshold)) {
      $diffThreshold = $this->config['diff_threshold'];
    }
    settype($diffThreshold, 'int');

    $images = array();
    $files = scandir($dir);
    foreach ($files as $file) {
      if (substr($file, -4) == '.jpg') {
        $images[] = $dir . '/' . $file;
      }
    }

    if (empty($images)) {
      return;
    }

    // create the destination dir
    if (!file_exists($destinationDir)) {
      if (!mkdir($destinationDir, 0777, true)) {
        throw new \Exception('Unable to create Path [' . $destinationDir . ']');
      }
    }

    $i = 0;
    foreach ($images as $key => $image) {

      // copy the first image
      if ($key == 0) {
        $img = trim(str_replace($dir, '', $images[$key]), '/');
        $this->copyWithDatestamp($i, $dir, $destinationDir, $img);
        $i++;
      } else {
        $prev = $key - 1;
        $val = $this->compare($images[$prev], $images[$key]);

        $img = trim(str_replace($dir, '', $images[$key]), '/');
        if ($val > $diffThreshold) {
          $text = $img . ': <info>' . $val . '</info>';
          $this->writeln($text);

          // copy image to the destination directory
          $this->copyWithDatestamp($i, $dir, $destinationDir, $img);
          $i++;
        } else {
          $this->writeln($img . ': ' . $val);
        }
      }
    }

    // Create a video of the detected images
    $this->createVideo($destinationDir);
  }

  /**
   * Compute how different two images are.
   *
   * @see http://www.imagemagick.org/Usage/compare/
   */
  public function compare($img_a, $img_b, $fuzz = 10) {
    $cmd = 'compare -metric AE -fuzz ' . escapeshellarg($fuzz) . '% ' . escapeshellarg($img_a) . '  ' . escapeshellarg($img_b) . ' null: 2>&1';
    //$process = new Process($cmd);
    $this->process->setCommandLine($cmd);
    $this->process->run();
    if (!$this->process->isSuccessful()) {
      throw new \RuntimeException($this->process->getErrorOutput());
    }
    $output = $this->process->getOutput();
    return trim($output);
  }

  public function createVideo($dir) {
    // avconv -y -v quiet -r 1 -f image2 -i img_%04d.jpg -r 25 -b 65536k a.avi
    $dir = escapeshellarg($dir);
    $cmd = 'avconv -y -v quiet -r 1 -f image2 -i ' . $dir . '/img_%04d.jpg -r 25 -b 65536k ' . $dir . '/activity.avi';
    $this->process->setCommandLine($cmd);
    $this->process->run();
    if (!$this->process->isSuccessful()) {
      throw new \RuntimeException($this->process->getErrorOutput());
    }
    $output = $this->process->getOutput();
    return trim($output);
  }

  public function deleteOldDirectories($processed = false) {

    if (!empty($processed)) {
      $dirPrefix = 'p_';
    } else {
      $dirPrefix = 'in_';
    }

    if (!empty($this->config['days_old'])) {
      $daysOld = $this->config['days_old'];
    } else {
      $daysOld = 14;
    }

    $old = $daysOld * 24 * 60 * 60;
    $nt = time();
    $dir = $this->saveDir;
    $files = scandir($dir);
    foreach ($files as $file) {
      if (substr($file, 0, strlen($dirPrefix)) == $dirPrefix) {
        $date = str_replace($dirPrefix, '', $file);
        $ut = strtotime($date);
        $age = $nt - $ut;
        if ($age > $old) {
          $this->deleteDirectory($dir . '/' . $file);
        }
      }
    }
  }

  public function deleteDirectory($dir) {
    if (is_dir($dir)) {
      // delete the contents
      $files = scandir($dir);
      foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
          if (is_dir($dir . '/' . $file)) {
            $this->deleteDirectory($dir . '/' . $file);
          } else {
            unlink($dir . '/' . $file);
          }
        }
      }
      // delete directory
      rmdir($dir);
    }
  }

  protected function copyWithDatestamp($i, $source, $destination, $imgName) {
    $string = $imgName;
    if ($im = imagecreatefromjpeg($source . '/' . $imgName)) {
      $textColor = imagecolorallocate ($im, 0, 0,0);
      imagestring ($im, 5, 3, 3, $string, $textColor);
      imagejpeg($im, $destination . '/img_' . sprintf('%04d', $i) . '.jpg', 100);
    }
  }

  protected function writeln($str) {
    if (!empty($this->output)) {
      $this->output->writeln($str);
    }
  }

}
