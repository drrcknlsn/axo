<?php

namespace Drrcknlsn\Axo\Command\Backlog;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkableCommand extends Command
{
    private const FETCH_QUANTITY = 10;
    private const TYPE_MAP = [
        'defects' => 'Bug',
        'features' => 'Enh',
        'tasks' => 'Tsk',
    ];
    private const IGNORED_STATUSES = [
        'Complete',
        'Non-Issue',
        'Ready for Testing',
        'Ready for Technical Review',
    ];
    private const MAX_ATTEMPTS = 5;
    protected static $defaultName = 'backlog:workable';
    private $apiClient;

    protected function configure()
    {
        $this->setDescription('Lists the tasks.');
        $this->apiClient = new ApiClient();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = $this->filterResults($this->getItems());
        $page = 2;
        while ($page <= self::MAX_ATTEMPTS && count($output) < self::FETCH_QUANTITY) {
            $output = array_merge(
                $output,
                $this->filterResults($this->getItems($page, self::FETCH_QUANTITY - count($output)))
            );
            $page++;
        }
        $this->writeOutput($output);

        return 0;
    }

    private function filterResults(array $items): array
    {
        return array_filter($items['data'], function (array $item): bool {
            return !in_array($item['workflow_step']['name'], self::IGNORED_STATUSES);
        });
    }

    private function getItems(int $page = 1, int $quantity = self::FETCH_QUANTITY)
    {
        return $this->apiClient->getItems([
            'columns' => [
                'id',
                'item_type',
                'name',
                'workflow_step',
            ],
            'filter_id' => [
                // todo: where can these be moved to?
                273, // Super Backlog - Dev Pickup (Tasks)
                271, // Super Backlog - Dev Pickup (Bugs)
                272, // super Backlog - Dev Pickup (Enhancements)
                57, // Ticket Exclusion (Tickets)
                // These are the admin overview filters, which is less useful for this command
                // 268, // Super Backlog - Workable (Bugs)
                // 269, // Super Backlog - Workable (Enhancements)
                // 270, // Super Backlog - Workable (Tasks)
            ],
            'page_size' => $quantity,
            'page' => $page,
            'sort_fields' => 'rank',
        ]);
    }

    private function writeOutput(array $items): void
    {
        foreach ($items as $i => $item) {
            printf(
                "%2s) [%s:%4s] (%s) %s\n",
                $i + 1,
                self::TYPE_MAP[$item['item_type']],
                $item['id'],
                $item['workflow_step']['name'],
                $item['name']
            );
        }
    }
}
