<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;

class DeleteCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'dump:delete';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {dump? : Dump file name}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Delete without confirmation}';

    /**
     * @var string
     */
    protected $description = 'Delete dump file on AWS';

    /**
     * @return void
     *
     * @throws \League\Flysystem\FilesystemException
     * @throws \PhpSchool\CliMenu\Exception\InvalidTerminalException
     */
    public function handle(): void
    {
        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        AwsS3::initAwsBucket($this->output, $initProgress);

        // Get dump file
        $projectPrefix = AppConfig::getConfigValue('project') ? AppConfig::getConfigValue('project') . '/' : '';
        $dumpFile = $this->argument('dump')
            ? $projectPrefix . $this->argument('dump')
            : AwsS3::getAwsDumpFile('Delete Dump', AppConfig::getConfigValue('project'));
        if (!$dumpFile) {
            $this->error('Dump name is not specified.');
            return;
        }

        // Check if the dump exit on AWS
        $awsDisk = AwsS3::getAwsDisk();
        $hasAwsDump = $awsDisk->has($dumpFile);
        if (!$hasAwsDump) {
            $this->error(sprintf('<comment>%s</comment> dump file is not found.', $dumpFile));
            return;
        }

        if (!$this->option('force')
            && !$this->confirm(sprintf('Do you really want to delete <comment>%s</comment> dump?', $dumpFile), true)
        ) {
            return;
        }

        $awsDisk->delete($dumpFile);
        $this->info(sprintf('Deleted <comment>%s</comment> dump.', $dumpFile));
    }
}
