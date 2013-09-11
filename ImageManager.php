<?php
namespace Theapi\CctvBundle;

use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

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
        $this->copyWithInfo($i, $dir, $destinationDir, $img);
        $i++;
      } else {
        $prev = $key - 1;
        $val = $this->compare($images[$prev], $images[$key]);

        $img = trim(str_replace($dir, '', $images[$key]), '/');
        if ($val > $diffThreshold) {
          $text = $img . ': <info>' . $val . '</info>';
          $this->writeln($text);

          // copy image to the destination directory
          $this->copyWithInfo($i, $dir, $destinationDir, $img);
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
    $dir = escapeshellarg($dir);

    // avconv -y -v quiet -r 1 -f image2 -i img_%04d.jpg -r 25 -b 65536k a.avi
    //$cmd = 'avconv -y -v quiet -r 1 -f image2 -i ' . $dir . '/img_%04d.jpg -r 25 -b 65536k ' . $dir . '/activity.avi';

    // avconv -y -v quiet -r 1 -f image2 -i img_%04d.jpg -vcodec libx264 -preset fast -r 25 activity.mp4
    $cmd = 'avconv -y -r 1 -f image2 -i ' . $dir . '/img_%04d.jpg -vcodec libx264 -preset fast -r 25 ' . $dir . '/activity.mp4';

    $this->process->setCommandLine($cmd);
    $this->process->setTimeout(3600);
    $this->process->run(array($this, 'writeProcessCallbackln'));

    if (!$this->process->isSuccessful()) {
      throw new \RuntimeException($this->process->getErrorOutput());
    }
  }

  public function getVideoFile($date = null) {

    if ($date === null) {
      // find the latest video
      $findLatest = true;
    }

    if (empty($date)) {
      // Use the directory for today
      $date = date('Y-m-d');
    } else {
      // enforce date is in the format Y-m-d
      $date = date('Y-m-d', strtotime($date));
    }

    $dir = $this->saveDir . '/in_' . $date;
    if (is_dir($dir)) {
      $processedDir = $this->saveDir . '/p_' . $date;
      $finder = new Finder();
      $finder->files()->in($processedDir)->name('activity.mp4');
      foreach ($finder as $file) {
        return $processedDir . '/' . $file->getRelativePathname();
      }
    }

    if (!empty($findLatest)) {
      $finder = new Finder();
      $finder->files()->in($this->saveDir)->depth('== 2')->name('activity.mp4')->sortByModifiedTime();
      foreach ($finder as $file) {
        // sortByModifiedTime() returns oldest first, so get the last one
        $path = $this->saveDir . '/' . $file->getRelativePathname();
      }
      if (isset($path)) {
        return $path;
      }
    }

    throw new \Exception('Video for ' . $date . ' not found');
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

    public function writeln($str)
    {
        if (!empty($this->output)) {
            $this->output->writeln($str);
        }
    }

    public function writeProcessCallbackln($type, $buffer)
    {
        if (!empty($this->output)) {
            $this->output->writeln($buffer);
        }
    }

    /**
    * Get images for the given date
    *
    * @param string $date
    * @throws \Exception
    * @return string
    */
    public function getImages($date = null, $limit = 10)
    {

        if ($date === null) {
            // find the latest directory
            $findLatest = true;
        }

        if (empty($date)) {
            // Use the directory for today
            $date = date('Y-m-d');
        } else {
            // enforce date is in the format Y-m-d
            $date = date('Y-m-d', strtotime($date));
        }

        $dir = $this->saveDir . '/in_' . $date;
        $files = array();
        if (is_dir($dir)) {
            $finder = new Finder();
            $finder->files()->in($dir)->name('*.jpg')->sortByName();
            foreach ($finder as $file) {
                $files[] =  '/in_' . $date . '/' . $file->getRelativePathname();
            }

            return array_slice($files, -$limit);
        }

        if (!empty($findLatest)) {
            $scan = scandir($this->saveDir, 1); // descending order (not provided by Finder)

            foreach ($scan as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (substr($file, 0, 3) == 'in_' && is_dir($this->saveDir . '/' . $file)) {
                    $dir = $file;
                    $finder = new Finder();
                    $finder->files()->in($this->saveDir . '/' . $dir)->name('*.jpg')->sortByName();
                    foreach ($finder as $file) {
                        $files[] = $dir . '/' . $file->getRelativePathname();
                    }
                    if (count($files) > 0) {

                        return array_slice($files, -$limit);
                    }
                }
            }

        }

        throw new \Exception('Images not found');
    }

    public function getImage($id = null)
    {

        if (isset($this->images[$id])) {
            return $this->images[$id];
        }

        if ($id == null) {
            try {
                $files = $this->getImages(null, 2);
                $this->images[$id] = $files[0];

                return $this->saveDir . '/' . $this->images[$id];
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    public function getPreviousImage($id = null)
    {

    }

    public function getNextImage($id = null)
    {

    }


    protected function copyWithInfo($i, $source, $destination, $imgName)
    {
        $string = $imgName;
        if ($im = imagecreatefromjpeg($source . '/' . $imgName)) {
            $height = 20;
            $width = 240;
            $backColor = imagecolorallocatealpha($im, 255, 255, 255, 90);
            imagefilledrectangle($im, 0, 0, $width, $height, $backColor);
            $textColor = imagecolorallocate ($im, 0, 0,0);
            imagestring ($im, 5, 3, 3, $string, $textColor);
            imagejpeg($im, $destination . '/img_' . sprintf('%04d', $i) . '.jpg', 100);
        }
    }
}
