<?php

namespace Drrcknlsn\Axo\Command;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListBugFiltersCommand extends Command
{
    protected static $defaultName = 'bug:list_filters';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $filters = $apiClient->getFilters('defects');

        $output->writeln(json_encode($bug, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
