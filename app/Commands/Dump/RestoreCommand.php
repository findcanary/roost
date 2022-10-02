<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;

class RestoreCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'dump:restore';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {dump? : Dump file name}'
        . ' {--r|most-recent : Restore most recent dump}'
        . ' {--t|tag= : A tag of the dump files}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Overwrite local file if exits}'
        . ' {--k|keep-file : Keep dump file}';

    /**
     * @var string
     */
    protected $description = 'Download and import dump from AWS';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dump = $this->argument('dump');
        if (empty($dump) && $this->option('most-recent')) {
            $project = AppConfig::getConfigValue('project');
            if (empty($project)) {
                $this->error('Project is not specified.');
                return;
            }

            $tag = $this->option('tag');
            $dump = $this->getMostRecentDump($project, $tag);
            if (empty($dump)) {
                $tagInfo = $tag ? sprintf(' and [%s] tag', $tag) : '';
                $this->error(sprintf('There is not found dump for %s project%s.', $project, $tagInfo));
                return;
            }
        }

        $this->call(
            DownloadCommand::COMMAND,
            [
                'dump' => $dump,
                '--magento-directory' => $this->option('magento-directory'),
                '--db-host' => $this->option('db-host'),
                '--db-port' => $this->option('db-port'),
                '--db-name' => $this->option('db-name'),
                '--db-username' => $this->option('db-username'),
                '--db-password' => $this->option('db-password'),
                '--storage' => $this->option('storage'),
                '--aws-bucket' => $this->option('aws-bucket'),
                '--aws-access-key' => $this->option('aws-access-key'),
                '--aws-secret-key' => $this->option('aws-secret-key'),
                '--aws-region' => $this->option('aws-region'),
                '--project' => $this->option('project'),
                '--no-progress' => $this->option('no-progress'),
                '--force' => $this->option('force'),
                '--quiet' => $this->option('quiet'),
                '--import' => true,
                '--remove-file' => !$this->option('keep-file')
            ]
        );
    }

    private function getMostRecentDump(string $project, ?string $tag = null): ?string
    {
        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        AwsS3::initAwsBucket($this->output, $initProgress);

        $dumpItems = AwsS3::getAwsProjectDumps($project, $tag);
        $dumpItems = array_reverse($dumpItems);

        return $dumpItems[0]['name'] ?? null;
    }
}
