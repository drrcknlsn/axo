<?php

namespace Drrcknlsn\Axo\Command\Task;

use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    protected static $defaultName = 'task:show';

    protected function configure()
    {
        $this->setDescription('Displays a given task.');

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The ID of the task to show'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $task = $apiClient->getTask($input->getArgument('id'));

        $project = $apiClient->getProject($task['project']['id']);
        $task['project'] = $project['name'];

        $workflowStep = $apiClient->getWorkflowStep($task['workflow_step']['id']);
        $task['workflow_step'] = $workflowStep['name'];

        $release = $apiClient->getRelease($task['release']['id']);
        $task['release'] = $release['name'];

        $assignedTo = $apiClient->getUser($task['assigned_to']['id']);
        $task['assigned_to'] = implode(' ', [
            $assignedTo['first_name'],
            $assignedTo['last_name'],
        ]);

        $status = $apiClient->getPicklistItem('status', $task['status']['id']);
        $task['status'] = $status['name'];

        $priority = $apiClient->getPicklistItem('priority', $task['priority']['id']);
        $task['priority'] = $priority['name'];

        $reportedBy = $apiClient->getUser($task['reported_by']['id']);
        $task['reported_by'] = implode(' ', [
            $reportedBy['first_name'],
            $reportedBy['last_name'],
        ]);

        if ($task['estimated_duration']['time_unit']['id'] === 0) {
            $task['estimated_duration'] = null;
        } else {
            $estimated = $apiClient->getPicklistItem('time_units', $task['estimated_duration']['time_unit']['id']);
            $task['estimated_duration'] = sprintf(
                '%s %s',
                $task['estimated_duration']['duration'],
                $estimated['name']
            );
        }

        if ($task['remaining_duration']['time_unit']['id'] === 0) {
            $task['remaining_duration'] = null;
        } else {
            $remaining = $apiClient->getPicklistItem('time_units', $task['remaining_duration']['time_unit']['id']);
            $task['remaining_duration'] = sprintf(
                '%s %s',
                $task['remaining_duration']['duration'],
                $remaining['name']
            );
        }

        if ($task['actual_duration']['time_unit']['id'] === 0) {
            $task['actual_duration'] = null;
        } else {
            $actual = $apiClient->getPicklistItem('time_units', $task['actual_duration']['time_unit']['id']);
            $task['actual_duration'] = sprintf(
                '%s %s',
                $task['actual_duration']['duration'],
                $actual['name']
            );
        }

        if ($task['category']['id'] === 0) {
            $task['category'] = null;
        } else {
            $category = $apiClient->getPicklistItem('category', $task['category']['id']);
            $task['category'] = $category['name'];
        }

        $desc = $task['description'];
        unset($task['description']);

        $list = array_chunk(
            array_map(function ($value) {
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    return json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                } else {
                    return $value;
                }
            }, $task),
            1,
            true
        );

        $io = new SymfonyStyle($input, $output);
        $io->definitionList(...$list);

        $io->text($desc);

        return 0;
    }
}
