<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Commands\Dump\CleanCommand;

class BackupCommand extends Command
{
    use AppCommand;

    const COMMAND = 'db:backup';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--t|tag= : A tag of the dump file}'
        . ' {--s|strip= : Tables to strip (dump only structure of those tables)}'
        . ' {--no-progress : Do not display progress}'
        . ' {--print : Print command only, not run it}'
        . ' {--skip-filter : Do not filter DEFINER and ROW_FORMAT}'
        . ' {--compatibility : mysqldump 8 backward compatible with MySQL 5.7}'
        . ' {--c|clean= : The number of latest dumps to keep, other will be removed}'
        . ' {--f|force : Overwrite dump file if it already exits locally}'
        . ' {--k|keep-file : Keep dump file}';

    /**
     * @var string
     */
    protected $description = 'Export and upload DB to AWS';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->call(
            ExportCommand::COMMAND,
            [
                'file' => $this->argument('file'),
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
                '--tag' => $this->option('tag'),
                '--strip' => $this->option('strip'),
                '--compatibility' => $this->option('compatibility'),
                '--no-progress' => $this->option('no-progress'),
                '--print' => $this->option('print'),
                '--skip-filter' => $this->option('skip-filter'),
                '--project' => $this->option('project'),
                '--force' => $this->option('force'),
                '--quiet' => $this->option('quiet'),
                '--upload' => true,
                '--remove-file' => !$this->option('keep-file')
            ]
        );

        $clean = (int)$this->option('clean');
        if ($clean > 0) {
            $this->call(
                CleanCommand::COMMAND,
                [
                    'count' => $clean,
                    '--project' => $this->option('project'),
                    '--tag' => $this->option('tag'),
                ]
            );
        }
    }
}
