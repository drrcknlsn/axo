<?php

namespace Drrcknlsn\Axo\Command\Features;

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
    protected static $defaultName = 'features:show';

    protected function configure()
    {
        $this->setDescription('Displays a given feature.');

        $this->setDefinition(new InputDefinition([
            new InputOption('desc', 'd'),
            new InputOption('full', 'f'),
        ]));

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The ID of the feature to show'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiClient = new ApiClient();
        $item = $apiClient->getFeature($input->getArgument('id'));

        if ($item['parent']['id'] === 0) {
            $item['parent'] = null;
        } else {
            $parent = $apiClient->getFeature($item['parent']['id']);
            $item['parent'] = sprintf('[%s] %s', $parent['id'], $parent['name']);
        }

        $project = $apiClient->getProject($item['project']['id']);
        $item['project'] = $project['name'];

        $workflowStep = $apiClient->getWorkflowStep($item['workflow_step']['id']);
        $item['workflow_step'] = $workflowStep['name'];

        $release = $apiClient->getRelease($item['release']['id']);
        $item['release'] = $release['name'];

        if ($item['assigned_to']['id'] === 0) {
            $item['assigned_to'] = null;
        } else {
            $assignedTo = $apiClient->getUser($item['assigned_to']['id']);
            $item['assigned_to'] = implode(' ', [
                $assignedTo['first_name'],
                $assignedTo['last_name'],
            ]);
        }

        if ($item['status']['id'] === 0) {
            $item['status'] = null;
        } else {
            $status = $apiClient->getPicklistItem('status', $item['status']['id']);
            $item['status'] = $status['name'];
        }

        if ($item['priority']['id'] === 0) {
            $item['priority'] = null;
        } else {
            $priority = $apiClient->getPicklistItem('priority', $item['priority']['id']);
            $item['priority'] = $priority['name'];
        }

        if ($item['reported_by']['id'] === 0) {
            $item['reported_by'] = null;
        } else {
            $reportedBy = $apiClient->getUser($item['reported_by']['id']);
            $item['reported_by'] = implode(' ', [
                $reportedBy['first_name'],
                $reportedBy['last_name'],
            ]);
        }

        if ($item['estimated_duration']['time_unit']['id'] === 0) {
            $item['estimated_duration'] = null;
        } else {
            $estimated = $apiClient->getPicklistItem('time_units', $item['estimated_duration']['time_unit']['id']);
            $item['estimated_duration'] = sprintf(
                '%s %s',
                $item['estimated_duration']['duration'],
                $estimated['name']
            );
        }

        if ($item['remaining_duration']['time_unit']['id'] === 0) {
            $item['remaining_duration'] = null;
        } else {
            $remaining = $apiClient->getPicklistItem('time_units', $item['remaining_duration']['time_unit']['id']);
            $item['remaining_duration'] = sprintf(
                '%s %s',
                $item['remaining_duration']['duration'],
                $remaining['name']
            );
        }

        if ($item['actual_duration']['time_unit']['id'] === 0) {
            $item['actual_duration'] = null;
        } else {
            $actual = $apiClient->getPicklistItem('time_units', $item['actual_duration']['time_unit']['id']);
            $item['actual_duration'] = $this->formatDuration(
                $item['actual_duration']['duration'],
                $actual['name']
            );
        }

        if (isset($item['category'])) {
            if ($item['category']['id'] === 0) {
                $item['category'] = null;
            } else {
                $category = $apiClient->getPicklistItem('category', $item['category']['id']);
                $item['category'] = $category['name'];
            }
        }

        $item['start_date'] = $item['start_date']
            ? $this->formatDateTime($item['start_date'])
            : null;
        $item['last_updated_date_time'] = $item['last_updated_date_time']
            ? $this->formatDateTime($item['last_updated_date_time'])
            : null;

        foreach ($item['custom_fields'] as $name => $value) {
            $field = $apiClient->getCustomField('features', $name);
            // TODO(derrick): Do value translations.
            $item['_' . $field['label']] = $value;
        }
        unset($item['custom_fields']);

        $title = sprintf('[%s] %s', $item['id'], $item['name']);
        $desc = $item['description'];
        unset($item['description']);

        if (!$input->getOption('full')) {
            // Hide stuff we don't want to show in a summary.
            unset(
                $item['archived'],
                $item['completion_date'],
                $item['due_date'],
                $item['estimated_duration'],
                $item['id'],
                $item['is_completed'],
                $item['item_type'],
                $item['name'],
                $item['number'],
                $item['percent_complete'],
                $item['publicly_viewable'],
                $item['remaining_duration'],
                $item['reported_by_customer_contact']
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
            }, $item),
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

            $comments = $apiClient->getFeatureComments($input->getArgument('id'));

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
