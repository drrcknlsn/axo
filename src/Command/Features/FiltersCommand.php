<?php

namespace Drrcknlsn\Axo\Command\Features;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersCommand extends Command
{
    protected static $defaultName = 'features:filters';

    protected function configure()
    {
        $this->setDescription('Lists the feature filters.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $filters = $apiClient->getFilters('features');

        $output->writeln(json_encode($filters, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
