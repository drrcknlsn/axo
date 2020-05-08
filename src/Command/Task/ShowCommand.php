<?php

namespace Drrcknlsn\Axo\Command\Task;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
    protected static $defaultName = 'task:show';

    protected function configure()
    {
        $this->setDescription('Displays a given task.');

        $this->addArgument(
            'num',
            InputArgument::REQUIRED,
            'Which task number do you want to show?'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $task = $apiClient->getTask($input->getArgument('num'));

        $output->writeln(json_encode($task, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
