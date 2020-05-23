<?php

declare(strict_types = 1);

namespace App\Services;

use App\Config;
use App\Facades\AppConfig;

class Pdo
{
    /**
     * @param string|null $dbName
     * @return bool
     *
     * @throws \PDOException
     */
    public static function validateConfiguration(string $dbName = null): bool
    {
        $pdo = static::createPDO($dbName);
        $pdo->exec('SELECT 1');
        return true;
    }

    /**
     * @param string|null $dbName
     * @return \PDO
     */
    public static function createPDO(string $dbName = null): \PDO
    {
        $dbName = $dbName ?: (string)AppConfig::getConfigValue(Config::KEY_DB_NAME);
        return new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s;port=%s;charset=utf8',
                $dbName,
                AppConfig::getConfigValue(Config::KEY_DB_HOST),
                AppConfig::getConfigValue(Config::KEY_DB_PORT)
            ),
            AppConfig::getConfigValue(Config::KEY_DB_USERNAME),
            AppConfig::getConfigValue(Config::KEY_DB_PASSWORD)
        );
    }
}
