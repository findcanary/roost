<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Commands\Database\ImportCommand;
use App\Traits\Command\Dump;
use App\Traits\Command\AwsS3;
use App\Traits\Command\Menu;

class DownloadCommand extends Command
{
    use Dump, AwsS3, Menu;

    const COMMAND = 'dump:download';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {dump? : Dump file name}'
        . ' {--i|import : Import downloaded dump}'
        . ' {--r|remove-file : Remove file after import}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Overwrite local file if exits}';

    /**
     * @var string
     */
    protected $description = 'Download dump from AWS';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->initDumpDisk();

        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        $this->initAwsBucket($initProgress);

        // Get dump file
        $projectPrefix = $this->getConfigValue('project') ? $this->getConfigValue('project') . '/' : '';
        try {
            $dumpFile = $this->argument('dump')
                ? $projectPrefix . $this->argument('dump')
                : $this->getAwsDumpFile('Download Dump', $this->getConfigValue('project'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        if (!$dumpFile) {
            $this->error('Dump name is not specified.');
            return;
        }

        // Check if the dump exit on AWS
        $awsDisk = $this->getAwsDisk();
        try {
            $hasAwsDump = $awsDisk->has($dumpFile);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error($e->getAwsErrorMessage());
            return;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        if (!$hasAwsDump) {
            $this->error(sprintf('<comment>%s</comment> dump file is not found.', $dumpFile));
            return;
        }

        $dbFile = basename($dumpFile);

        // Download the dump
        $dumpDisk = $this->getDumpDisk();
        if (!$dumpDisk->has($dbFile)
            || $this->option('force')
            || $this->confirm(sprintf('<comment>%s</comment> dump already exists locally. Overwrite it?', $dbFile), true)
        ) {
            $this->info(sprintf('Downloading <comment>%s</comment>', $dumpFile));

            try {
                $dumpDisk->write($dbFile, $awsDisk->readStream($dumpFile));
            } catch (\Aws\S3\Exception\S3Exception $e) {
                $this->error($e->getAwsErrorMessage());
                return;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }

            $this->info(sprintf('Downloaded: <comment>%s</comment>', $dumpDisk->getAdapter()->applyPathPrefix($dbFile)));
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

        try {
            $dbDisk = $this->getDumpDisk();
            $dbDisk->delete($dbFile);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
