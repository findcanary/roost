<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Traits\Command\Dump;
use App\Traits\Command\AwsS3;
use App\Traits\Command\Menu;
use App\Traits\Command\Database;
use Illuminate\Support\Facades\File;

class UploadCommand extends Command
{
    use Database, Dump, AwsS3, Menu;

    const COMMAND = 'dump:upload';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Overwrite file if exits}';

    /**
     * @var string
     */
    protected $description = 'Upload dump file onto AWS';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->initDumpDisk();

        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        $this->initAwsBucket($initProgress);

        $fileName = $this->argument('file');
        $fileName = $fileName || $this->option('quiet') ? $fileName : $this->getDumpName('Upload Dump');
        if (empty($fileName)) {
            $this->error('Dump file is not specified.');
            return;
        }

        $dbPath = $this->getDumpPath($fileName);
        if (!$this->verifyPath($dbPath)) {
            $this->error(sprintf('Passed path does not exist or not a file: %s', $dbPath));
            return;
        }

        $project = $this->getConfigValue('project');
        $awsFileName = $project ? $project . '/' . File::basename($dbPath) : File::basename($dbPath);

        $awsDisk = $this->getAwsDisk();
        try {
            $hasAwsDump = $awsDisk->has($awsFileName);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error($e->getAwsErrorMessage());
            return;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        if ($hasAwsDump
            && !$this->option('force')
            && !$this->confirm(sprintf('<comment>%s</comment> file already exists. Do you want to overwrite it?', $awsFileName), true)
        ) {
            return;
        }

        $this->info(sprintf('Uploading <comment>%s</comment>', $awsFileName));

        try {
            $awsDisk->writeStream($awsFileName, $this->readStream($dbPath));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error($e->getAwsErrorMessage());
            return;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info(sprintf('Uploaded: <comment>%s</comment>.', $awsFileName));
    }

    /**
     * @param string $filePath
     * @return false|resource
     */
    private function readStream(string $filePath)
    {
        return fopen($filePath, 'rb');
    }

    /**
     * @param string $dbPath
     * @return bool
     */
    private function verifyPath(string $dbPath): bool
    {
        return File::exists($dbPath) && File::isFile($dbPath);
    }
}
