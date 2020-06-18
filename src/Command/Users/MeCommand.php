<?php

namespace Drrcknlsn\Axo\Command\Users;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MeCommand extends Command
{
    protected static $defaultName = 'users:me';

    protected function configure()
    {
        $this->setDescription('Lists me.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $users = $apiClient->getMe();

        $output->writeln(json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
