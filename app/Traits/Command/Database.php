<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use Illuminate\Support\Facades\File;
use App\Config;
use App\Shell\Command\Mysql;
use App\Shell\Command\Mysqldump;
use App\Shell\Command\Sed;

trait Database
{
    use Pdo;

    /**
     * @var string[]
     */
    private $systemDbs = ['sys', 'mysql', 'performance_schema', 'information_schema'];

    /**
     * @var array
     */
    private $supportedIncomeFiles = [
        'gz',
        'zip',
        'sql'
    ];

    /**
     * @var array
     */
    private $supportedOutcomeFiles = [
        'gz',
        'sql'
    ];

    /**
     * @param string $filename
     * @return bool
     */
    private function isIncomeFileSupported(string $filename): bool
    {
        return in_array(File::extension($filename), $this->supportedIncomeFiles, true);
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function isOutcomeFileSupported(string $filename): bool
    {
        return in_array(File::extension($filename), $this->supportedOutcomeFiles, true);
    }

    /**
     * @return array
     */
    private function getExistingDatabases(): array
    {
        $mysqlCommand = $this->createMysqlCommand();
        $mysqlCommand->arguments(['-N', '-e', 'SHOW DATABASES']);
        $dbsString = $mysqlCommand->run();

        $dbs = array_filter(explode(PHP_EOL, $dbsString));
        return array_diff($dbs, $this->systemDbs);
    }

    /**
     * @param string|null $dbName
     * @return array
     */
    private function getAllTables(string $dbName = null): array
    {
        $result = $this->createPDO($dbName)->query('SHOW TABLES');
        return $result->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return \App\Shell\Command\Mysql
     */
    private function createMysqlCommand(): Mysql
    {
        $dbPassword = $this->getConfigValue(Config::KEY_DB_PASSWORD);
        $envVars = $dbPassword ? ['MYSQL_PWD' => $dbPassword] : [];

        $mysqlCommand = new Mysql($envVars);
        $mysqlCommand->arguments($this->getCredentialArguments());
        return $mysqlCommand;
    }

    /**
     * @return \App\Shell\Command\Mysqldump
     */
    private function createMysqldumpCommand(): Mysqldump
    {
        $dbPassword = $this->getConfigValue(Config::KEY_DB_PASSWORD);
        $envVars = $dbPassword ? ['MYSQL_PWD' => $dbPassword] : [];

        $mysqldumpCommand = new Mysqldump($envVars);
        $mysqldumpCommand->arguments($this->getCredentialArguments());
        return $mysqldumpCommand;
    }

    /**
     * @return array
     */
    private function getCredentialArguments(): array
    {
        $credentials = [
            'host' => $this->getConfigValue(Config::KEY_DB_HOST),
            'user' => $this->getConfigValue(Config::KEY_DB_USERNAME),
            'port' => $this->getConfigValue(Config::KEY_DB_PORT)
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
    private function createSedCommand(): Sed
    {
        return new Sed(['LANG' => 'C', 'LC_CTYPE' => 'C', 'LC_ALL' => 'C']);
    }

    /**
     * @return \App\Shell\Command\Sed[]
     */
    private function getFilterCommands(): array
    {
        return [
            $this->createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/'"]),
            $this->createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*PROCEDURE/PROCEDURE/'"]),
            $this->createSedCommand()->arguments(['-e', "'s/DEFINER[ ]*=[ ]*[^*]*FUNCTION/FUNCTION/'"]),
            $this->createSedCommand()->arguments(['-e', "'s/ROW_FORMAT=FIXED//g'"]),
        ];
    }
}
