<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use App\Command;
use App\Traits\Command\Database;

class CreateCommand extends Command
{
    use Database;

    const COMMAND = 'db:create';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}'
        . ' {--f|force : Delete if exist}';

    /**
     * @var string
     */
    protected $description = 'Create Database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->argument('name') ?: $this->getConfigValue('db-name');
        $dbName = $dbName ?: $this->ask('Enter Db name');
        if (!$dbName) {
            $this->error('DB name is not specified.');
            return;
        }

        if ($this->option('force')) {
            $this->call(DropCommand::COMMAND, ['name' => $dbName]);
        }

        $this->task(sprintf('Create DB "%s" if not exists', $dbName), function () use ($dbName) {
            try {
                $mysqlCommand = $this->createMysqlCommand();
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
