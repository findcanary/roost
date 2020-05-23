<?php

declare(strict_types = 1);

namespace App\Services;

use App\Config;
use App\Facades\AppConfig;
use App\Shell\Command\Mysql;
use App\Shell\Command\Mysqldump;
use App\Shell\Command\Sed;

class Database
{
    /**
     * @var string[]
     */
    private static $systemDbs = ['sys', 'mysql', 'performance_schema', 'information_schema'];

    /**
     * @return array
     */
    public static function getExistingDatabases(): array
    {
        $mysqlCommand =  static::createMysqlCommand();
        $mysqlCommand->arguments(['-N', '-e', 'SHOW DATABASES']);
        $dbsString = $mysqlCommand->run();

        $dbs = array_filter(explode(PHP_EOL, $dbsString));
        return array_diff($dbs, static::$systemDbs);
    }

    /**
     * @param string|null $dbName
     * @return array
     */
    public static function getAllTables(string $dbName = null): array
    {
        $result = Pdo::createPDO($dbName)->query('SHOW TABLES');
        return $result->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return \App\Shell\Command\Mysql
     */
    public static function createMysqlCommand(): Mysql
    {
        $dbPassword = AppConfig::getConfigValue(Config::KEY_DB_PASSWORD);
        $envVars = $dbPassword ? ['MYSQL_PWD' => $dbPassword] : [];

        $mysqlCommand = new Mysql($envVars);
        $mysqlCommand->arguments(static::getCredentialArguments());
        return $mysqlCommand;
    }

    /**
     * @return \App\Shell\Command\Mysqldump
     */
    public static function createMysqldumpCommand(): Mysqldump
    {
        $dbPassword = AppConfig::getConfigValue(Config::KEY_DB_PASSWORD);
        $envVars = $dbPassword ? ['MYSQL_PWD' => $dbPassword] : [];

        $mysqldumpCommand = new Mysqldump($envVars);
        $mysqldumpCommand->arguments(static::getCredentialArguments());
        return $mysqldumpCommand;
    }

    /**
     * @return array
     */
    private static function getCredentialArguments(): array
    {
        $credentials = [
            'host' => AppConfig::getConfigValue(Config::KEY_DB_HOST),
            'user' => AppConfig::getConfigValue(Config::KEY_DB_USERNAME),
            'port' => AppConfig::getConfigValue(Config::KEY_DB_PORT)
        ];

        $arguments = [];
        foreach ($credentials as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $arguments[] = sprintf('--%s=%s', $key, $value);
        }

        return $arguments;
    }

    /**
     * @return \App\Shell\Command\Sed
     */
    private static function createSedCommand(): Sed
    {
        return new Sed(['LANG' => 'C', 'LC_CTYPE' => 'C', 'LC_ALL' => 'C']);
    }

    /**
     * @return \App\Shell\Command\Sed[]
     */
    public static function getFilterCommands(): array
    {
        return [
            static::createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/'"]),
            static::createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*PROCEDURE/PROCEDURE/'"]),
            static::createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*FUNCTION/FUNCTION/'"]),
            static::createSedCommand()->arguments(['-e', "'s/ROW_FORMAT=FIXED//g'"]),
        ];
    }
}
