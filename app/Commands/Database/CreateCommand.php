<?php

declare(strict_types=1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\Database;

class CreateCommand extends Command
{
    use AppCommand;

    const COMMAND = 'db:create';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}'
        . ' {--f|force : Re-create if DB with the same name already exists}';

    /**
     * @var string
     */
    protected $description = 'Create Database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->argument('name') ?: AppConfig::getConfigValue('db-name');
        $dbName = $dbName ?: $this->ask('Enter Db name');
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        if ($this->option('force')) {
            $this->call(DropCommand::COMMAND, ['name' => $dbName]);
        }

        $this->task(sprintf('Create DB "%s" if not exists', $dbName), static function () use ($dbName) {
            try {
                $mysqlCommand = Database::createMysqlCommand();
                $mysqlCommand->arguments(['-e', sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $dbName)]);
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
}
