<?php

namespace Drrcknlsn\Axo\Command\Bug;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
    protected static $defaultName = 'bug:show';

    protected function configure()
    {
        $this->setDescription('Displays a given bug.');

        $this->addArgument(
            'num',
            InputArgument::REQUIRED,
            'Which bug number do you want to show?'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $bug = $apiClient->getBug($input->getArgument('num'));

        $output->writeln(json_encode($bug, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
