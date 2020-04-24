<?php

declare(strict_types = 1);

namespace App\Commands\Config;

use App\Command;
use Symfony\Component\Yaml\Yaml;

class DumpCommand extends Command
{
    const COMMAND = 'config:dump';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {--table-groups : Include Table Groups details}';

    /**
     * @var string
     */
    protected $description = 'Print config values';

    /**
     * @return void
     */
    public function handle(): void
    {
        $config = $this->toConfigArray();
        if (!$this->option('table-groups')) {
            unset($config['table-groups']);
        }
        $this->info(Yaml::dump($config));
    }
}
