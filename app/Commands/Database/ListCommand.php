<?php

declare(strict_types = 1);

namespace App\Commands\Database;

use App\Command;
use App\Traits\Command\Database;
use App\Traits\Command\Dump;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends Command
{
    use Database, Dump;

    const COMMAND = 'db:list';

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

        if (!$this->validateConfiguration()) {
            return;
        }

        $dbList = $this->getExistingDatabases();
        if (!empty($search)) {
            $dbList = array_filter($dbList, static function ($dbName) use ($search) {
                return strpos($dbName, $search) !== false;
            });
        }

        $tableRows = array_map(static function ($dbName) {
            return [$dbName];
        }, $dbList);

        $table = new Table($this->output);
        $table->setHeaders(['Databases']);
        $table->setRows($tableRows);
        $table->render();
    }
}
