<?php

declare(strict_types=1);

namespace App\Services;

use App\Shell\Command\Warden;

class WardenDatabase
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
        $wardenCommand =  static::createWardenDbCommand('connect');
        $wardenCommand->arguments(['-N', '-e', '"SHOW DATABASES"']);

        $output = null;
        $wardenCommand->exec($output);

        $dbs = self::parseResult($output);
        return array_diff($dbs, static::$systemDbs);
    }

    /**
     * @param string|null $dbName
     * @return array
     */
    public static function getAllTables(string $dbName = null): array
    {
        $wardenCommand =  static::createWardenDbCommand('connect');
        if ($dbName) {
            $wardenCommand->argument($dbName);
        }
        $wardenCommand->arguments(['-N', '-e', '"SHOW TABLES"']);

        $output = null;
        $wardenCommand->exec($output);

        return self::parseResult($output);
    }

    /**
     * @param string|null $command
     * @return \App\Shell\Command\Warden
     */
    public static function createWardenDbCommand(string $command = null): Warden
    {
        $wardenCommand = new Warden();
        $wardenCommand->argument('db');
        if ($command) {
            $wardenCommand->argument($command);
        }
        return $wardenCommand;
    }

    /**
     * @param array|null $result
     * @return array
     */
    private static function parseResult(array $result = null): array
    {
        $result = !empty($result) ? array_slice($result, 1, -1) : [];
        return array_map(
            static function ($row) {
                return trim(substr($row, 1, -1));
            },
            $result
        );
    }
}
