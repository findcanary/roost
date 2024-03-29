<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;
use App\Services\Dump;
use App\Commands\Database\ImportCommand;
use App\Commands\Warden\ImportCommand as WardenImportCommand;

class DownloadCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'dump:download';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {dump? : Dump file name}'
        . ' {--i|import : Import downloaded dump}'
        . ' {--w|import-warden : Import downloaded dump into Warden Db container}'
        . ' {--r|remove-file : Remove file after import}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Overwrite local file if exits without confirmation}';

    /**
     * @var string
     */
    protected $description = 'Download dump from AWS';

    /**
     * @return void
     *
     * @throws \League\Flysystem\FilesystemException
     * @throws \PhpSchool\CliMenu\Exception\InvalidTerminalException
     */
    public function handle(): void
    {
        Dump::initDumpDisk();

        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        AwsS3::initAwsBucket($this->output, $initProgress);

        // Get dump file
        $projectPrefix = AppConfig::getConfigValue('project') ? AppConfig::getConfigValue('project') . '/' : '';
        $dumpFile = $this->argument('dump')
            ? $projectPrefix . $this->argument('dump')
            : AwsS3::getAwsDumpFile('Download Dump', AppConfig::getConfigValue('project'));
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

        $dbFile = basename($dumpFile);

        // Download the dump
        $dumpFilesystem = Dump::getDumpFilesystem();
        if (!$dumpFilesystem->has($dbFile)
            || $this->option('force')
            || $this->confirm(sprintf('<comment>%s</comment> dump already exists locally. Overwrite it?', $dbFile), true)
        ) {
            $this->info(sprintf('Downloading <comment>%s</comment>', $dumpFile));
            $dumpFilesystem->writeStream($dbFile, $awsDisk->readStream($dumpFile));
            $this->info(sprintf('Downloaded: <comment>%s</comment>', $dumpFilesystem->path($dbFile)));
        }

        // Import the dump
        if ($this->option('import')) {
            $this->info('Import the dump:');

            $this->call(
                ImportCommand::COMMAND,
                [
                    'file' => $dbFile,
                    '--no-progress' => $this->option('no-progress'),
                    '--quiet' => $this->option('quiet'),
                ]
            );

            $this->processDeletingFile($dbFile);
        } elseif ($this->option('import-warden')) {
            $this->info('Import the dump:');

            $this->call(
                WardenImportCommand::COMMAND,
                [
                    'file' => $dbFile,
                    '--no-progress' => $this->option('no-progress'),
                    '--quiet' => $this->option('quiet'),
                ]
            );

            $this->processDeletingFile($dbFile);
        }
    }

    /**
     * @param string $dbFile
     * @return void
     */
    private function processDeletingFile(string $dbFile): void
    {
        if (!$this->option('remove-file')) {
            return;
        }

        $dumpFilesystem = Dump::getDumpFilesystem();
        $dumpFilesystem->delete($dbFile);
    }
}
