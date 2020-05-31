<?php

declare(strict_types = 1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @method static array|string|null getConfigValue(string $configKey)
 * @method static string|null setConfigValue(string $configKey, string $configValue):
 * @method static array toConfigArray()
 * @method static void ensureAppConfigInitialized(InputInterface $input)
 *
 * @see \App\Config
 */
class AppConfig extends Facade
{
    /**
     * @return string
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app-config';
    }
}
