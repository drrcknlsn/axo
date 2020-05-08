<?php

namespace Drrcknlsn\Axo\Command;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListBugsCommand extends Command
{
    protected static $defaultName = 'bug:list';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $bugs = $apiClient->getBugs();

        $output->writeln(json_encode($bugs, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
