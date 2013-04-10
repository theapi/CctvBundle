<?php
namespace Theapi\CctvBundle\Command;

use Theapi\CctvBundle\ImageManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input,
    Symfony\Component\Console\Input\ArrayInput,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('cctv:cron')
            ->setDescription('Commands to be run daily by cron')
            ->setHelp('
15 7 * * * %kernel.root_dir% %command.name% cron -q
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

      // Process last night's images
      $command = $this->getApplication()->find('cctv:images');
      $input = new ArrayInput(array('command' => 'images'));
      $returnCode = $command->run($input, $output);

      // Process all of yesterday's images
      $command = $this->getApplication()->find('cctv:images');
      $arguments = array(
          'command' => 'images',
          'date'    => 'yesterday',
      );
      $input = new ArrayInput($arguments);
      $returnCode = $command->run($input, $output);

      // Purge old inbox directories
      $command = $this->getApplication()->find('cctv:images');
      $arguments = array(
          'command' => 'images',
          'date'    => 'purge',
      );
      $input = new ArrayInput($arguments);
      $returnCode = $command->run($input, $output);

      // Purge old processed directories
      $command = $this->getApplication()->find('cctv:images');
      $arguments = array(
          'command'     => 'images',
          'date'        => 'purge',
          '--processed' => true,
      );
      $input = new ArrayInput($arguments);
      $returnCode = $command->run($input, $output);

    }

}
