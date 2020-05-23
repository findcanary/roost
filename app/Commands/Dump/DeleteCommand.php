<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Traits\Command\AwsS3;
use App\Traits\Command\Menu;

class DeleteCommand extends Command
{
    use AwsS3, Menu;

    const COMMAND = 'dump:delete';

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
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function handle(): void
    {
        $initProgress = !$this->option('no-progress') && !$this->option('quiet');
        $this->initAwsBucket($initProgress);

        // Get dump file
        $projectPrefix = $this->getConfigValue('project') ? $this->getConfigValue('project') . '/' : '';
        $dumpFile = $this->argument('dump')
            ? $projectPrefix . $this->argument('dump')
            : $this->getAwsDumpFile('Delete Dump', $this->getConfigValue('project'));
        if (!$dumpFile) {
            $this->error('Dump name is not specified.');
            return;
        }

        // Check if the dump exit on AWS
        $awsDisk = $this->getAwsDisk();
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
