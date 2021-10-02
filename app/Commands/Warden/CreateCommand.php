<?php

declare(strict_types=1);

namespace App\Commands\Warden;

use App\Services\WardenDatabase;
use App\Traits\Command as AppCommand;
use LaravelZero\Framework\Commands\Command;

class CreateCommand extends Command
{
    use AppCommand;

    const COMMAND = 'warden:db:create';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}'
        . ' {--f|force : Re-create if DB with the same name already exists}';

    /**
     * @var string
     */
    protected $description = 'Create Database from Warden db container';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->argument('name');

        if ($this->option('force')) {
            $this->call(DropCommand::COMMAND, ['name' => $dbName]);
        }

        $taskMessage = $dbName
            ? sprintf('Create DB %s if not exists', $dbName)
            : 'Create DB if not exists';

        $this->task($taskMessage, static function () use ($dbName) {
            try {
                $wardenCommand = WardenDatabase::createWardenDbCommand('create');
                if ($dbName) {
                    $wardenCommand->argument($dbName);
                }
                $wardenCommand->exec();

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
