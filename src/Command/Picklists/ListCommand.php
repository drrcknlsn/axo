<?php

namespace Drrcknlsn\Axo\Command\Picklists;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    protected static $defaultName = 'picklists:list';

    protected function configure()
    {
        $this->setDescription('Lists the picklists.');

        $this->addArgument(
            'type',
            InputArgument::REQUIRED,
            'The type of picklist'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $picklist = $apiClient->getPicklist($input->getArgument('type'));

        $output->writeln(json_encode($picklist, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
