<?php

namespace Drrcknlsn\Axo\Command\Items;

use Drrcknlsn\Axo\ApiClient;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TakeCommand extends Command
{
    private const ITEM_TYPES = [
        'bug' => 'Bug',
        'task' => 'Task',
        'feature' => 'Feature',
    ];
    protected static $defaultName = 'item:take';

    protected function configure()
    {
        $this->setDescription('Lists the items.');
        $this->addArgument('id', InputArgument::REQUIRED, 'Item ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        try {
            $task = $apiClient->getBug($input->getArgument('id'));
            $type = self::ITEM_TYPES['bug'];
        } catch (RequestException $e) {
            try {
                $task = $apiClient->getTask($input->getArgument('id'));
                $type = self::ITEM_TYPES['task'];
            } catch (RequestException $e) {
                $task = $apiClient->getFeature($input->getArgument('id'));
                $type = self::ITEM_TYPES['feature'];
            }
        }
        $apiClient->{'update' . $type}($input->getArgument('id'), ['assigned_to' => $apiClient->getMe()['id']]);
        return 0;
    }
}
