<?php

namespace Drrcknlsn\Axo\Command\Task;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomFieldsCommand extends Command
{
    protected static $defaultName = 'task:custom_fields';

    protected function configure()
    {
        $this->setDescription('Lists the task custom fields.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $fields = $apiClient->getCustomFields('tasks');

        $output->writeln(json_encode($fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
