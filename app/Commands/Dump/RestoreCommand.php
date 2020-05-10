<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Traits\Command\AwsS3;
use App\Traits\Command\Dump;
use App\Traits\Command\Menu;

class RestoreCommand extends Command
{
    use Dump, AwsS3, Menu;

    const COMMAND = 'dump:restore';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {dump? : Dump file name}'
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
        $this->call(
            DownloadCommand::COMMAND,
            [
                'dump' => $this->argument('dump'),
                '--magento-directory' => $this->option('magento-directory'),
                '--db-host' => $this->option('db-host'),
                '--db-port' => $this->option('db-port'),
                '--db-name' => $this->option('db-name'),
                '--db-username' => $this->option('db-username'),
                '--db-password' => $this->option('db-password'),
                '--dump-dir' => $this->option('dump-dir'),
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
}
