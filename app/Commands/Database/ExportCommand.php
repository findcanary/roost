<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\Archive;
use App\Services\Database;
use App\Services\Dump;
use App\Services\DumpFile;
use App\Services\DbStrip;
use App\Services\Progress;
use App\Services\Pdo;
use App\Shell\Pipe;
use App\Shell\Command\Pv;
use App\Commands\Dump\UploadCommand;

class ExportCommand extends Command
{
    use AppCommand;

    const COMMAND = 'db:export';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {file? : File name}'
        . ' {--t|tag= : A tag the dump file}'
        . ' {--f|force : Overwrite file if exits}'
        . ' {--s|strip= : Tables to strip (dump only structure of those tables)}'
        . ' {--no-progress : Do not display progress}'
        . ' {--print : Print export command}'
        . ' {--skip-filter : Do not filter DEFINER and ROW_FORMAT}'
        . ' {--u|upload : Upload the dump to AWS}';

    /**
     * @var string
     */
    protected $description = 'Export and Gzip Database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = AppConfig::getConfigValue('db-name') ?: $this->getDbName();
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        Pdo::validateConfiguration();

        $fileName = $this->argument('file');
        if (empty($fileName)) {
            $defaultName = $this->getDefaultDumpName(
                (AppConfig::getConfigValue('project') ?: $dbName),
                $this->option('tag')
            );
            $fileName = $this->option('no-interaction') ? $defaultName : $this->getDumpName($defaultName);
        }
        $dumpPath = Dump::getDumpPath($this->updateDumpExtension($fileName));

        $strip = (string)$this->option('strip');
        $stripTableList = !empty($strip) ? DbStrip::getTableList($strip, Database::getAllTables($dbName)) : [];
        if (!empty($stripTableList)) {
            $structurePipe = new Pipe();
            $structurePipe->command(
                Database::createMysqldumpCommand()
                    ->arguments([
                        '--default-character-set=utf8',
                        '--add-drop-table',
                        '--no-data',
                        $dbName
                    ])
                    ->arguments($stripTableList)
            );

            if (!$this->option('skip-filter')) {
                $structurePipe->commands(Database::getFilterCommands());
            }

            Archive::addArchiveCommand($dumpPath, $structurePipe);

            $structurePipe->getLastCommand()->output($dumpPath);

            if ($this->option('print')) {
                $this->line($structurePipe->toString());
            } else {
                $structurePipe->passthru();
            }
        }

        $pipe = new Pipe();
        $pipe->command(
            Database::createMysqldumpCommand()->arguments([
                '--routines=true',
                '--add-drop-table',
                '--default-character-set=utf8',
                $dbName
            ])
        );

        if (!empty($stripTableList)) {
            $stripTableArguments = array_map(static function ($table) use ($dbName) {
                return sprintf('--ignore-table=%s', $dbName . '.' . $table);
            }, $stripTableList);
            $pipe->getLastCommand()->arguments($stripTableArguments);
        }

        if (!$this->option('no-progress') && !$this->option('quiet') && Progress::isPvAvailable()) {
            $pipe->command(
                (new Pv)->arguments(['-b', '-t', '-w', '80', '-N', 'Export'])
            );
        }

        if (!$this->option('skip-filter')) {
            $pipe->commands(Database::getFilterCommands());
        }

        Archive::addArchiveCommand($dumpPath, $pipe);

        $pipe->getLastCommand()->output($dumpPath, !empty($stripTableList));

        if ($this->option('print')) {
            $this->line($pipe->toString());
        } else {
            $pipe->passthru();
            $this->comment(sprintf('DB <info>%s</info> is exported to <info>%s</info>', $dbName, $dumpPath));
        }

        if (!$this->option('print') && $this->option('upload')) {
            $this->info('Upload the dump:');

            $this->call(
                UploadCommand::COMMAND,
                [
                    'file' => $dumpPath,
                    '--project' => $this->option('project'),
                    '--no-progress' => $this->option('no-progress'),
                    '--force' => $this->option('force'),
                    '--quiet' => $this->option('quiet'),
                ]
            );
        }
    }

    /**
     * @return string|null
     */
    private function getDbName(): ?string
    {
        return $this->askWithCompletion('Enter DB name', Database::getExistingDatabases());
    }

    /**
     * @param string $defaultName
     * @return string
     */
    private function getDumpName(string $defaultName): string
    {
        return $this->ask('Enter Dump file name (location)', $defaultName);
    }

    /**
     * @param string $identifier
     * @param string|null $tag
     * @return string
     */
    private function getDefaultDumpName(string $identifier, ?string $tag = null): string
    {
        $tagSuffix = !empty($tag) ? '[' . $tag . ']' : '';
        return $identifier . '-' . gmdate('Y.m.d') . '-' . gmdate('H.i.s') . $tagSuffix . '.sql.gz';
    }

    /**
     * @param string $file
     * @return string
     */
    private function updateDumpExtension(string $file): string
    {
        return DumpFile::isOutcomeFileSupported($file) ? $file : $file . '.sql.gz';
    }
}
