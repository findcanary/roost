<?php

declare(strict_types=1);

namespace App\Commands\Database;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Services\Pdo;
use App\Services\Database;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'db:list';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {search? : Filter the list}';

    /**
     * @var string
     */
    protected $description = 'Display DB list';

    /**
     * @return void
     */
    public function handle(): void
    {
        $search = $this->argument('search');

        Pdo::validateConfiguration();

        $dbList = Database::getExistingDatabases();
        if (!empty($search)) {
            $dbList = array_filter($dbList, static function ($dbName) use ($search) {
                return str_contains($dbName, $search);
            });
        }

        $dbRows = array_map(static function ($dbName) {
            return [$dbName];
        }, $dbList);

        $table = new Table($this->output);
        $table->setHeaders(['Databases']);
        $table->setRows($dbRows);
        $table->render();
    }
}
