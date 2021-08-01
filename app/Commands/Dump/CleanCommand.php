<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;

class CleanCommand extends Command
{
    use AppCommand;

    const COMMAND = 'dump:clean';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {count : The number of latest dumps to keep}'
        . ' {--t|tag= : A tag of the dump files}';

    /**
     * @var string
     */
    protected $description = 'Clean dumps on AWS per project';

    /**
     * @return void
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function handle(): void
    {
        AwsS3::initAwsBucket($this->output, false);

        $count = (int)$this->argument('count');
        if ($count < 1) {
            return;
        }

        $project = AppConfig::getConfigValue('project');
        if (empty($project)) {
            $this->error('Project is not specified.');
            return;
        }

        $dumpItems = AwsS3::getAwsProjectDumps($project, $this->option('tag'));
        $forDelete = array_slice($dumpItems, 0, -1 * $count);

        $awsDisk = AwsS3::getAwsDisk();
        foreach ($forDelete as $dump) {
            $awsDisk->delete($dump['path']);
        }
    }
}
