<?php
namespace Theapi\CctvBundle\Command;

use Theapi\CctvBundle\MailSender;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class EmailTestCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('cctv:email:test')
            ->addOption(
               'spool',
               null,
               InputOption::VALUE_NONE,
               'Send it via the spool'
            )
            ->setDescription('Send a test email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $container = $this->getContainer();

      $mailer = $container->get('theapi_cctv.mail_sender');

      $viaSpool = $input->getOption('spool');
      $sent = $mailer->sendTestMail($viaSpool);
      if ($viaSpool) {
        $output->writeln(sprintf('Spooled <info>%s</info>', $sent));
      }
      else {
        $output->writeln(sprintf('Sent <info>%s</info>', $sent));
      }
    }

}
