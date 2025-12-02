<?php

namespace Hgabka\EmailBundle\Command;

use Hgabka\EmailBundle\Helper\MessageSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hgabka:email:send-messages', description: 'Sends messages in queue', hidden: false)]
class SendMessagesCommand extends Command
{
    public function __construct(protected readonly MessageSender $messageSender)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Sends out messages from the queue')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum how many messages should be sent?',
                10
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

    protected function send($limit, OutputInterface $output): void
    {
        $output->writeln('Sending messages...');

        $result = $this
            ->messageSender
            ->sendMessageQueue($limit)
        ;
        $output->writeln(sprintf(
            'Total [%d] message(s) / success [%d] / failed [%d]',
            $result['total'],
            $result['sent'],
            $result['fail']
        ));
    }
}
