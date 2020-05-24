<?php

declare(strict_types = 1);

namespace App\Commands\Config;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use Symfony\Component\Yaml\Yaml;

class DumpCommand extends Command
{
    use AppCommand;

    const COMMAND = 'config:dump';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {--table-groups : Include Table Groups details}';

    /**
     * @var string
     */
    protected $description = 'Print configurations';

    /**
     * @return void
     */
    public function handle(): void
    {
        $configData = AppConfig::toConfigArray();
        ksort($configData);

        if (!$this->option('table-groups')) {
            unset($configData['table-groups']);
        }
        $this->info(Yaml::dump($configData));
    }
}
