<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use Illuminate\Support\Facades\File;
use App\Command;
use App\Shell\Pipe;
use App\Shell\Command\Pv;
use App\Shell\Command\Cat;
use App\Traits\Command\Archive;
use App\Traits\Command\Database;
use App\Traits\Command\Progress;
use App\Traits\Command\Dump;
use App\Traits\Command\Menu;
use App\Traits\FormattedFileSize;

class ImportCommand extends Command
{
    use Database, Dump, Archive, Progress, Menu, FormattedFileSize;

    const COMMAND = 'db:import';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--no-progress : Do not display progress}'
        . ' {--print : Print export command}'
        . ' {--skip-filter : Do not filter DEFINER and ROW_FORMAT}';

    /**
     * @var string
     */
    protected $description = 'Import Database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->getConfigValue('db-name') ?: $this->ask('Enter Db name');
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        $fileName = $this->argument('file');
        $fileName = $fileName || $this->option('quiet') ? $fileName : $this->getDumpName('Import DB');
        if ($fileName === null) {
            $this->error('Dump file is not specified.');
            return;
        }

        $dbPath = $this->getDumpPath($fileName);
        if (!$this->verifyPath($dbPath)) {
            $this->error(sprintf('Passed path does not exist or not a file: %s', $dbPath));
            return;
        }
        $originDbPath = $dbPath;

        $fileType = File::extension($fileName);
        if (!$this->isIncomeFileSupported($fileName)) {
            $this->error(sprintf('The file type is not supported: %s', $fileType));
            return;
        }

        if (!$this->option('print')) {
            $this->call(CreateCommand::COMMAND, ['name' => $dbName, '--force' => true]);
        }

        if (!$this->validateConfiguration($dbName)) {
            return;
        }

        $tmpFilePath = tempnam(env('TMPDIR'), 'dbtoolbox_');
        $pipeUnarchive = new Pipe();
        $isUnarchive = $this->addUnarchiveCommand($dbPath, $pipeUnarchive);
        if ($isUnarchive) {

            if (!$this->option('no-progress') && !$this->option('quiet') && $this->isPvAvailable()) {
                $pipeUnarchive->command(
                    (new Pv)->arguments(['-b', '-t', '-w', '80', '-N', 'Unpack'])
                );
            }

            $pipeUnarchive->getLastCommand()->output($tmpFilePath);
            if ($this->option('print')) {
                $this->line($pipeUnarchive->toString());
            } else {
                $pipeUnarchive->passthru();
            }

            $dbPath = $tmpFilePath;
        }


        $pipe = new Pipe();

        if (!$this->option('no-progress') && !$this->option('quiet') && $this->isPvAvailable()) {
            $pipe->command(
                (new Pv)->arguments([$dbPath, '-w', '80', '-N', 'Export'])
            );
        } else {
            $pipe->command(
                (new Cat)->argument($dbPath)
            );
        }

        if (!$this->option('skip-filter')) {
            $pipe->commands($this->getFilterCommands());
        }

        $pipe->command(
            $this->createMysqlCommand()->arguments(['--force', $dbName])
        );

        if ($this->option('print')) {
            $this->line($pipe->toString());
            return;
        }

        $pipe->passthru();

        File::delete($tmpFilePath);

        $this->comment(sprintf('DB <info>%s</info> is imported from dump <info>%s</info>', $dbName, $originDbPath));
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
