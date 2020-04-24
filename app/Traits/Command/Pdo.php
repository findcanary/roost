<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use App\Config;

trait Pdo
{
    /**
     * @param string|null $dbName
     * @return bool
     */
    public function validateConfiguration(string $dbName = null): bool
    {
        try {
            $pdo = $this->createPDO($dbName);
            $pdo->exec('SELECT 1');
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param string|null $dbName
     * @return \PDO
     */
    public function createPDO(string $dbName = null): \PDO
    {
        $dbName = $dbName ?: (string)$this->getConfigValue(Config::KEY_DB_NAME);
        return new \PDO(
            sprintf(
                'mysql:dbname=%s;host=%s;port=%s;charset=utf8',
                $dbName,
                $this->getConfigValue(Config::KEY_DB_HOST),
                $this->getConfigValue(Config::KEY_DB_PORT)
            ),
            $this->getConfigValue(Config::KEY_DB_USERNAME),
            $this->getConfigValue(Config::KEY_DB_PASSWORD)
        );
    }
}
