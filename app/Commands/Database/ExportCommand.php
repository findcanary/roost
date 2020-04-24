<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use App\Command;
use App\Shell\Pipe;
use App\Shell\Command\Pv;
use App\Traits\Command\Archive;
use App\Traits\Command\Database;
use App\Traits\Command\Progress;
use App\Traits\Command\Dump;
use App\Traits\Command\DbStrip;

class ExportCommand extends Command
{
    use Database, Progress, Dump, DbStrip, Archive;

    const COMMAND = 'db:export';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}'
        . ' {--f|file= : File name}'
        . ' {--t|tag= : A tag the dump file}'
        . ' {--s|strip= : Tables to strip (dump only structure of those tables)}'
        . ' {--no-progress : Do not display progress}'
        . ' {--print : Print export command}'
        . ' {--skip-filter : Do not filter DEFINER and ROW_FORMAT}';

    /**
     * @var string
     */
    protected $description = 'Export and Gzip Database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->argument('name') ?: $this->getConfigValue('db-name');
        $dbName = $dbName ?: $this->getDbName();
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        if (!$this->validateConfiguration($dbName)) {
            return;
        }

        $fileName = $this->option('file');
        if (empty($fileName)) {
            $defaultName = $this->getDefaultDumpName($dbName, $this->option('tag'));
            $fileName = $this->option('no-interaction') ? $defaultName : $this->getDumpName($defaultName);
        }
        $dumpPath = $this->getDumpPath($this->updateDumpExtension($fileName));

        $strip = (string)$this->option('strip');
        $stripTableList = !empty($strip) ? $this->getTableList($strip, $this->getAllTables($dbName)) : [];
        if (!empty($stripTableList)) {
            $structurePipe = new Pipe();
            $structurePipe->command(
                $this->createMysqldumpCommand()
                    ->arguments([
                        '--default-character-set=utf8',
                        '--add-drop-table',
                        '--no-data',
                        $dbName
                    ])
                    ->arguments($stripTableList)
            );

            if (!$this->option('skip-filter')) {
                $structurePipe->commands($this->getFilterCommands());
            }

            $this->addArchiveCommand($dumpPath, $structurePipe);

            $structurePipe->getLastCommand()->output($dumpPath);

            if ($this->option('print')) {
                $this->line($structurePipe->toString());
            } else {
                $structurePipe->passthru();
            }
        }

        $pipe = new Pipe();
        $pipe->command(
            $this->createMysqldumpCommand()->arguments([
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

        if (!$this->option('no-progress') && !$this->option('quiet') && $this->isPvAvailable()) {
            $pipe->command(
                (new Pv)->arguments(['-b', '-t', '-w', '80', '-N', 'Export'])
            );
        }

        if (!$this->option('skip-filter')) {
            $pipe->commands($this->getFilterCommands());
        }

        $this->addArchiveCommand($dumpPath, $pipe);

        $pipe->getLastCommand()->output($dumpPath, !empty($stripTableList));

        if ($this->option('print')) {
            $this->line($pipe->toString());
        } else {
            $pipe->passthru();
        }

        $this->comment(sprintf('DB <info>%s</info> is exported to <info>%s</info>', $dbName, $dumpPath));
    }

    /**
     * @return string|null
     */
    private function getDbName(): ?string
    {
        return $this->askWithCompletion('Enter DB name', $this->getExistingDatabases());
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
        return $identifier . '-' . gmdate('Y.m.d') . '-' . $tagSuffix . gmdate('H.i.s') . '.sql.gz';
    }

    /**
     * @param string $file
     * @return string
     */
    private function updateDumpExtension(string $file): string
    {
        return $this->isOutcomeFileSupported($file) ? $file : $file . '.sql.gz';
    }
}
