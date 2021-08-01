<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;
use App\Services\Dump;
use Illuminate\Support\Facades\File;

class UploadCommand extends Command
{
    use AppCommand;

    const COMMAND = 'dump:upload';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--no-progress : Do not display progress}'
        . ' {--f|force : Overwrite file if exits without confirmation}';

    /**
     * @var string
     */
    protected $description = 'Upload dump file onto AWS';

    /**
     * @return void
     *
     * @throws \League\Flysystem\FileExistsException
     * @throws \PhpSchool\CliMenu\Exception\InvalidTerminalException
     */
    public function handle(): void
    {
        Dump::initDumpDisk();

        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        AwsS3::initAwsBucket($this->output, $initProgress);

        $fileName = $this->argument('file');
        $fileName = $fileName || $this->option('quiet') ? $fileName : Dump::getDumpName('Upload Dump');
        if (empty($fileName)) {
            $this->error('Dump file is not specified.');
            return;
        }

        $dbPath = Dump::getDumpPath($fileName);
        if (!$this->verifyPath($dbPath)) {
            $this->error(sprintf('Passed path does not exist or not a file: %s', $dbPath));
            return;
        }

        $project = AppConfig::getConfigValue('project');
        $awsFileName = $project ? $project . '/' . File::basename($dbPath) : File::basename($dbPath);

        $awsDisk = AwsS3::getAwsDisk();
        $hasAwsDump = $awsDisk->has($awsFileName);
        if ($hasAwsDump
            && !$this->option('force')
            && !$this->confirm(sprintf('<comment>%s</comment> file already exists. Do you want to overwrite it?', $awsFileName), true)
        ) {
            return;
        }

        $this->info(sprintf('Uploading <comment>%s</comment>', $awsFileName));

        $awsDisk->writeStream($awsFileName, $this->readStream($dbPath));
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
