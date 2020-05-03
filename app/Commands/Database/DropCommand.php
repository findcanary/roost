<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use App\Command;
use App\Traits\Command\Database;
use App\Traits\Command\Dump;

class DropCommand extends Command
{
    use Database, Dump;

    const COMMAND = 'db:drop';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}';

    /**
     * @var string
     */
    protected $description = 'Drop Database';

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

        $this->task(sprintf('Drop DB "%s" if exists', $dbName), function () use ($dbName) {
            try {
                $mysqlCommand = $this->createMysqlCommand();
                $mysqlCommand->arguments(['-e', sprintf('DROP DATABASE IF EXISTS `%s`', $dbName)]);
                $mysqlCommand->run();
                $result = true;
            } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
                $result = $e->getProcess()->getCommandLine();
            } catch (\Exception $e) {
                $result = $e->getMessage();
            }
            return $result;
        });
    }

    /**
     * @return string|null
     */
    private function getDbName(): ?string
    {
        return $this->askWithCompletion('Enter DB name', $this->getExistingDatabases());
    }
}
