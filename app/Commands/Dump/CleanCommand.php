<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Traits\Command\Dump;
use App\Traits\Command\AwsS3;

class CleanCommand extends Command
{
    use Dump, AwsS3;

    const COMMAND = 'dump:clean';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {count : The number of latest dumps to keep}'
        . ' {--t|tag= : A tag the dump file}';

    /**
     * @var string
     */
    protected $description = 'Clean dumps on AWS per project';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->initAwsBucket(false);

        $count = (int)$this->argument('count');
        if ($count < 1) {
            return;
        }

        $project = $this->getConfigValue('project');
        if (empty($project)) {
            $this->error('Project is not specified.');
            return;
        }

        $dumpItems = $this->getAwsDumpList()[$project] ?? [];
        usort($dumpItems, static function (array $a, array $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        $tag = $this->option('tag');
        if (!empty($tag)) {
            $tag = '[' . $tag . ']';
            $dumpItems = array_filter($dumpItems, static function ($dumpItem) use ($tag) {
                return strpos($dumpItem['name'], $tag) !== false;
            });
        }

        $forDelete = array_slice($dumpItems, 0, -1 * $count);

        $awsDisk = $this->getAwsDisk();
        foreach ($forDelete as $dump) {
            try {
                $awsDisk->delete($dump['path']);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                $this->error($e->getAwsErrorMessage());
                return;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        }
    }
}
