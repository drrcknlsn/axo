<?php

namespace Drrcknlsn\Axo\Command\Users;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
    protected static $defaultName = 'users:show';

    protected function configure()
    {
        $this->setDescription('Displays a given user.');

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The ID of the user to show.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $user = $apiClient->getUser($input->getArgument('id'));

        $output->writeln(json_encode($user, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
