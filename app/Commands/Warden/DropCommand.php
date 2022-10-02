<?php

declare(strict_types=1);

namespace App\Commands\Warden;

use App\Traits\Command as AppCommand;
use App\Services\WardenDatabase;
use LaravelZero\Framework\Commands\Command;

class DropCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'warden:db:drop';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {name? : Database name}';

    /**
     * @var string
     */
    protected $description = 'Drop Database from Warden db container';

    /**
     * @return void
     */
    public function handle(): void
    {
        $dbName = $this->argument('name');

        $taskMessage = $dbName
            ? sprintf('Drop DB "%s" if exists', $dbName)
            : 'Drop DB if exists';

        $this->task($taskMessage, static function () use ($dbName) {
            try {

                $wardenCommand = WardenDatabase::createWardenDbCommand('drop');
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
