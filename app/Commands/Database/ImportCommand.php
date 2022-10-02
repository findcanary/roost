<?php

declare(strict_types=1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\Archive;
use App\Services\Database;
use App\Services\Dump;
use App\Services\DumpFile;
use App\Services\Progress;
use App\Services\Directory;
use App\Services\Pdo;
use App\Shell\Pipe;
use App\Shell\Command\Pv;
use App\Shell\Command\Cat;
use Illuminate\Support\Facades\File;

class ImportCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'db:import';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--no-progress : Do not display progress}'
        . ' {--print : Print command only, not run it}'
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
        $dbName = AppConfig::getConfigValue('db-name') ?: $this->ask('Enter Db name');
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        $fileName = $this->argument('file');
        $fileName = $fileName || $this->option('quiet') ? $fileName : Dump::getDumpName('Import DB');
        if ($fileName === null) {
            $this->error('Dump file is not specified.');
            return;
        }

        $dbPath = Dump::getDumpPath($fileName);
        if (!$this->verifyPath($dbPath)) {
            $this->error(sprintf('Passed path does not exist or not a file: %s', $dbPath));
            return;
        }
        $originDbPath = $dbPath;

        $fileType = File::extension($fileName);
        if (!DumpFile::isIncomeFileSupported($fileName)) {
            $this->error(sprintf('The file type is not supported: %s', $fileType));
            return;
        }

        if (!$this->option('print')) {
            $this->call(CreateCommand::COMMAND, ['name' => $dbName, '--force' => true]);
        }

        Pdo::validateConfiguration();

        $tmpFilePath = tempnam(Directory::getTmpDirectory(), 'roost_tmp_dump_');
        $pipeUnarchive = new Pipe();
        $isUnarchive = Archive::addUnarchiveCommand($dbPath, $pipeUnarchive);
        if ($isUnarchive) {

            if (!$this->option('no-progress') && !$this->option('quiet') && Progress::isPvAvailable()) {
                $pipeUnarchive->command(
                    (new Pv())->arguments(['-b', '-t', '-w', '80', '-N', 'Unpack'])
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

        if (!$this->option('no-progress') && !$this->option('quiet') && Progress::isPvAvailable()) {
            $pipe->command(
                (new Pv())->arguments([$dbPath, '-w', '80', '-N', 'Import'])
            );
        } else {
            $pipe->command(
                (new Cat())->argument($dbPath)
            );
        }

        if (!$this->option('skip-filter')) {
            $pipe->commands(Database::getFilterCommands());
        }

        $pipe->command(
            Database::createMysqlCommand()->arguments(['--force', $dbName])
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
