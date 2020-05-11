<?php

namespace Drrcknlsn\Axo\Command\Bug;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersCommand extends Command
{
    protected static $defaultName = 'bug:filters';

    protected function configure()
    {
        $this->setDescription('Lists the bug filters.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $filters = $apiClient->getFilters('defects');

        $output->writeln(json_encode($filters, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
