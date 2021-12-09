<?php

namespace Hgabka\EmailBundle\Command;

use Hgabka\EmailBundle\Helper\MessageSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailsCommand extends Command
{
    protected static $defaultName = 'hgabka:email:send-emails';
    
    /** @var MessageSender */
    protected $messageSender;

    /**
     * SendMessagesCommand constructor.
     */
    public function __construct(MessageSender $messageSender)
    {
        parent::__construct();
        $this->messageSender = $messageSender;
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName(static::$defaultName)

            // the short description shown while running "php bin/console list"
            ->setDescription('Sends emails in queue')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Sends out emails from the queue')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum how many messages should be sent?',
                10
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time_start = microtime(true);

        $output->writeln('Sending starts...');

        $this->send($input->getOption('limit'), $output);

        $output->writeln('Sending done');
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $output->writeln("Execution time: $time seconds");
        
        return Command::SUCCESS;
    }

    protected function send($limit, OutputInterface $output)
    {
        $output->writeln('Sending messages...');

        $result = $this
            ->messageSender
            ->sendEmailQueue($limit)
        ;
        $output->writeln(sprintf(
            'Total [%d] message(s) / success [%d] / failed [%d]',
            $result['total'],
            $result['sent'],
            $result['fail']
        ));
    }
}
