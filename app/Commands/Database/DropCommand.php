<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\Database;

class DropCommand extends Command
{
    use AppCommand;

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
        $dbName = $this->argument('name') ?: AppConfig::getConfigValue('db-name');
        $dbName = $dbName ?: $this->getDbName();
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        $this->task(sprintf('Drop DB "%s" if exists', $dbName), static function () use ($dbName) {
            try {
                $mysqlCommand = Database::createMysqlCommand();
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
        return $this->askWithCompletion('Enter DB name', Database::getExistingDatabases());
    }
}
