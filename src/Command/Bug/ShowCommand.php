<?php

namespace Drrcknlsn\Axo\Command\Bug;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Drrcknlsn\Axo\ApiClient;
use HTMLPurifier;
use HTMLPurifier_Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    protected static $defaultName = 'bug:show';

    protected function configure()
    {
        $this->setDescription('Displays a given bug.');

        $this->setDefinition(new InputDefinition([
            new InputOption('desc', 'd'),
            new InputOption('full', 'f'),
        ]));

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The ID of the bug to show'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $bug = $apiClient->getBug($input->getArgument('id'));

        if ($bug['parent']['id'] === 0) {
            $bug['parent'] = null;
        } else {
            $parent = $apiClient->getBug($bug['parent']['id']);
            $bug['parent'] = sprintf('[%s] %s', $parent['id'], $parent['name']);
        }

        $project = $apiClient->getProject($bug['project']['id']);
        $bug['project'] = $project['name'];

        $workflowStep = $apiClient->getWorkflowStep($bug['workflow_step']['id']);
        $bug['workflow_step'] = $workflowStep['name'];

        $release = $apiClient->getRelease($bug['release']['id']);
        $bug['release'] = $release['name'];

        if ($bug['assigned_to']['id'] === 0) {
            $bug['assigned_to'] = null;
        } else {
            $assignedTo = $apiClient->getUser($bug['assigned_to']['id']);
            $bug['assigned_to'] = implode(' ', [
                $assignedTo['first_name'],
                $assignedTo['last_name'],
            ]);
        }

        if ($bug['status']['id'] === 0) {
            $bug['status'] = null;
        } else {
            $status = $apiClient->getPicklistItem('status', $bug['status']['id']);
            $bug['status'] = $status['name'];
        }

        if ($bug['priority']['id'] === 0) {
            $bug['priority'] = null;
        } else {
            $priority = $apiClient->getPicklistItem('priority', $bug['priority']['id']);
            $bug['priority'] = $priority['name'];
        }

        if ($bug['reported_by']['id'] === 0) {
            $bug['reported_by'] = null;
        } else {
            $reportedBy = $apiClient->getUser($bug['reported_by']['id']);
            $bug['reported_by'] = implode(' ', [
                $reportedBy['first_name'],
                $reportedBy['last_name'],
            ]);
        }

        if ($bug['estimated_duration']['time_unit']['id'] === 0) {
            $bug['estimated_duration'] = null;
        } else {
            $estimated = $apiClient->getPicklistItem('time_units', $bug['estimated_duration']['time_unit']['id']);
            $bug['estimated_duration'] = sprintf(
                '%s %s',
                $bug['estimated_duration']['duration'],
                $estimated['name']
            );
        }

        if ($bug['remaining_duration']['time_unit']['id'] === 0) {
            $bug['remaining_duration'] = null;
        } else {
            $remaining = $apiClient->getPicklistItem('time_units', $bug['remaining_duration']['time_unit']['id']);
            $bug['remaining_duration'] = sprintf(
                '%s %s',
                $bug['remaining_duration']['duration'],
                $remaining['name']
            );
        }

        if ($bug['actual_duration']['time_unit']['id'] === 0) {
            $bug['actual_duration'] = null;
        } else {
            $actual = $apiClient->getPicklistItem('time_units', $bug['actual_duration']['time_unit']['id']);
            $bug['actual_duration'] = $this->formatDuration(
                $bug['actual_duration']['duration'],
                $actual['name']
            );
        }

        if (isset($bug['category'])) {
            if ($bug['category']['id'] === 0) {
                $bug['category'] = null;
            } else {
                $category = $apiClient->getPicklistItem('category', $bug['category']['id']);
                $bug['category'] = $category['name'];
            }
        }

        $bug['start_date'] = $bug['start_date']
            ? $this->formatDateTime($bug['start_date'])
            : null;
        $bug['last_updated_date_time'] = $bug['last_updated_date_time']
            ? $this->formatDateTime($bug['last_updated_date_time'])
            : null;

        foreach ($bug['custom_fields'] as $name => $value) {
            $field = $apiClient->getCustomField('defects', $name);
            // TODO(derrick): Do value translations.
            $bug['_' . $field['label']] = $value;
        }
        unset($bug['custom_fields']);

        $title = sprintf('[%s] %s', $bug['id'], $bug['name']);
        $desc = $bug['description'];
        unset($bug['description']);

        if (!$input->getOption('full')) {
            // Hide stuff we don't want to show in a summary.
            unset(
                $bug['archived'],
                $bug['completion_date'],
                $bug['due_date'],
                $bug['estimated_duration'],
                $bug['id'],
                $bug['is_completed'],
                $bug['item_type'],
                $bug['name'],
                $bug['number'],
                $bug['percent_complete'],
                $bug['publicly_viewable'],
                $bug['remaining_duration'],
                $bug['reported_by_customer_contact']
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
            }, $bug),
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

            $comments = $apiClient->getBugComments($input->getArgument('id'));

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
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Trusted', true);
        $config->set('HTML.AllowedElements', [
            'a',
            'b',
            'br',
            'em',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'i',
            'img',
            'input',
            'li',
            'ol',
            'p',
            'strong',
            'u',
            'ul',
        ]);
        $purifier = new HTMLPurifier($config);
        $s = $purifier->purify($s);

        $s = preg_replace('#<(b|strong)[^>]*>(.*?)</\\1>#', '<options=bold>$2</>', $s);
        $s = preg_replace('#<(em|u)[^>]*>(.*?)</\\1>#', '<options=underscore>$2</>', $s);
        // Replace non-breaking spaces with spaces.
        $s = str_replace('&nbsp;', ' ', $s);
        $s = $this->links($s);
        $s = $this->images($s);
        $s = $this->mentions($s);
        // Strip all newlines.
        $s = preg_replace('#\R#', '', $s);
        // Replace <br> with newlines.
        $s = preg_replace('#<br[^>]*>#', "\n", $s);
        // Collapse superfluous empty lines.
        $s = preg_replace('#\R{3,}#', "\n\n", $s);
        // Decode entities.
        $s = html_entity_decode($s);
        $s = trim($s);
        // Trim trailing whitespace.
        $s = preg_replace('#\s+$#', '', $s);

        return $s;
    }

    private function links(string $s): string
    {
        return preg_replace(
            '#<a href="(.+?)">(.+?)</a>#',
            '<fg=green;options=bold>[LINK]</><href=$1>$2</><fg=green;options=bold>[/LINK]</>',
            $s
        );
    }

    private function images(string $s): string
    {
        return preg_replace(
            '#<img src="(.+?)"[^>]*>#',
            '<fg=magenta>[IMG]</><href=$1>$1</><fg=magenta>[/IMG]</>',
            $s
        );
    }

    private function mentions(string $s): string
    {
        return preg_replace(
            '#<input [^>]*value="(@ .+?)"[^>]*>#',
            '<fg=white;bg=blue;options=bold>$1</>',
            $s
        );
    }
}
