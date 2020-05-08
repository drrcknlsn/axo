<?php

namespace Drrcknlsn\Axo\Command\Task;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Drrcknlsn\Axo\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    protected static $defaultName = 'task:show';

    protected function configure()
    {
        $this->setDescription('Displays a given task.');

        $this->setDefinition(new InputDefinition([
            new InputOption('desc', 'd'),
            new InputOption('full', 'f'),
        ]));

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

        if ($task['parent']['id'] === 0) {
            $task['parent'] = null;
        } else {
            $parent = $apiClient->getTask($task['parent']['id']);
            $task['parent'] = sprintf('[%s] %s', $parent['id'], $parent['name']);
        }

        $project = $apiClient->getProject($task['project']['id']);
        $task['project'] = $project['name'];

        $workflowStep = $apiClient->getWorkflowStep($task['workflow_step']['id']);
        $task['workflow_step'] = $workflowStep['name'];

        $release = $apiClient->getRelease($task['release']['id']);
        $task['release'] = $release['name'];

        if ($task['assigned_to']['id'] === 0) {
            $task['assigned_to'] = null;
        } else {
            $assignedTo = $apiClient->getUser($task['assigned_to']['id']);
            $task['assigned_to'] = implode(' ', [
                $assignedTo['first_name'],
                $assignedTo['last_name'],
            ]);
        }

        $status = $apiClient->getPicklistItem('status', $task['status']['id']);
        $task['status'] = $status['name'];

        $priority = $apiClient->getPicklistItem('priority', $task['priority']['id']);
        $task['priority'] = $priority['name'];

        if ($task['reported_by']['id'] === 0) {
            $task['reported_by'] = null;
        } else {
            $reportedBy = $apiClient->getUser($task['reported_by']['id']);
            $task['reported_by'] = implode(' ', [
                $reportedBy['first_name'],
                $reportedBy['last_name'],
            ]);
        }

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
            $task['actual_duration'] = $this->formatDuration(
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

        $task['start_date'] = $this->formatDateTime($task['start_date']);
        $task['last_updated_date_time'] = $this->formatDateTime($task['last_updated_date_time']);

        $title = sprintf('[%s] %s', $task['id'], $task['name']);
        $desc = $task['description'];
        unset($task['description']);

        if (!$input->getOption('full')) {
            // Hide stuff we don't want to show in a summary.
            unset(
                $task['archived'],
                $task['completion_date'],
                $task['due_date'],
                $task['estimated_duration'],
                $task['id'],
                $task['is_completed'],
                $task['item_type'],
                $task['name'],
                $task['number'],
                $task['percent_complete'],
                $task['publicly_viewable'],
                $task['remaining_duration'],
                $task['reported_by_customer_contact']
            );
        }

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
        $io->title($title);

        $io->definitionList(...$list);

        if (
            $input->getOption('full')
            || $input->getOption('desc')
        ) {
            $io->section('Description');
            $output->writeLn($this->formatHtml($desc));
            $output->write("\n");
        }

        if ($input->getOption('full')) {
            $io->section('Comments');

            $comments = $apiClient->getTaskComments($input->getArgument('id'));

            usort($comments, function (array $a, array $b) {
                if (getenv('AXO_COMMENT_SORT') ?: 'desc' === 'desc') {
                    return $b['created_date_time'] <=> $a['created_date_time'];
                }

                return $a['created_date_time'] <=> $b['created_date_time'];
            });

            foreach ($comments as $comment) {
                $output->writeLn(sprintf(
                    '<fg=cyan>%s</> %s',
                    $comment['created_by_name'],
                    $this->formatDateTime($comment['created_date_time'])
                ));

                $output->writeLn($this->formatHtml($comment['comment_text']));
                $output->write("\n");
            }
        }

        return 0;
    }

    /**
     * TODO(derrick): Move this to a trait.
     */
    private function formatDateTime(string $s): string
    {
        $dt = Carbon::parse($s);

        return sprintf(
            '%s (%s)',
            $dt->toDayDateTimeString(),
            $dt->diffForHumans(['short' => true])
        );
    }

    /**
     * TODO(derrick): Move this to a trait.
     */
    private function formatDuration($n, $units): string
    {
        return CarbonInterval::make($n . ' ' . $units)
            ->forHumans(['short' => true]);
    }

    private function formatHtml(string $s): string
    {
        // Strip some tags we don't care about.
        $s = preg_replace('#<(span|u)[^>]*>(.*?)</\\1>#', '$2', $s);
        // Replace <strong>...</strong> with bolded text.
        $s = preg_replace('#<strong[^>]*>(.*?)</strong>#', '<options=bold>$1</>', $s);
        // Replace non-breaking spaces with spaces.
        $s = str_replace('&nbsp;', ' ', $s);
        $s = $this->links($s);
        $s = $this->mentions($s);
        // Replace <br> with newlines.
        $s = preg_replace('#<br[^>]*>#', "\n", $s);
        $s = html_entity_decode($s);
        $s = trim($s);
        // Trim trailing whitespace.
        $s = preg_replace('#\s+$#', '', $s);
        // Normalize line endings.
        $s = preg_replace('#\R#', "\n", $s);
        // Collapse superfluous empty lines.
        $s = preg_replace('#\R{3,}#', "\n\n", $s);

        return $s;
    }

    private function links(string $s): string
    {
        return preg_replace(
            '#<a href="(.+?)">(.+?)</a>#',
            '<href=$1>$2</>',
            $s
        );
    }

    private function mentions(string $s): string
    {
        return preg_replace(
            '#<input [^>]*data-mention="u_\d+" [^>]*value="(@.+?)"[^>]*>#',
            '<fg=white;bg=blue;options=bold>$1</>',
            $s
        );
    }
}
