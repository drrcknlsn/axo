<?php

namespace Drrcknlsn\Axo\Command\Backlog;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkableCommand extends Command
{
    protected static $defaultName = 'backlog:workable';

    protected function configure()
    {
        $this->setDescription('Lists the tasks.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $items = $apiClient->getItems([
            'columns' => [
                'id',
                'item_type',
                'name',
                'workflow_step',
            ],
            'filter_id' => [
                268, // Super Backlog - Workable (Bugs)
                269, // Super Backlog - Workable (Enhancements)
                270, // Super Backlog - Workable (Tasks)
                57, // Ticket Exclusion (Tickets)
            ],
            'page_size' => 10,
            'sort_fields' => 'rank',
        ]);

        $typeMap = [
            'defects' => 'Bug',
            'features' => 'Enh',
            'tasks' => 'Tsk',
        ];

        foreach ($items['data'] as $i => $item) {
            printf(
                "%2s) [%s:%4s] (%s) %s\n",
                $i + 1,
                $typeMap[$item['item_type']],
                $item['id'],
                $item['workflow_step']['name'],
                $item['name']
            );
        }

        return 0;
    }
}
