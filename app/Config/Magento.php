<?php

declare(strict_types = 1);

namespace App\Config;

use Illuminate\Support\Facades\File;
use App\Config;
use App\Services\DsnParts;
use App\Services\FilePath;
use App\Services\HomeDirectory;

class Magento
{
    /**
     * @var string
     */
    private $workingDirectory;

    /**
     * @param string $workingDirectory
     */
    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @return array
     */
    public function toConfigArray(): array
    {
        $env = $this->fetchEnvConfig();

        $config = [];
        $databaseHost = $this->getDatabaseInfo($env, 'host');
        if ($databaseHost) {
            $config[Config::KEY_DB_HOST] = $databaseHost;
        }

        $databasePort = $this->getDatabaseInfo($env, 'port');
        if ($databasePort) {
            $config[Config::KEY_DB_PORT] = $databasePort;
        }

        $databaseName = $this->getDatabaseName($env);
        if ($databaseName) {
            $config[Config::KEY_DB_NAME] = $databaseName;
        }

        $databaseUsername = $this->getDatabaseUsername($env);
        if ($databaseUsername) {
            $config[Config::KEY_DB_USERNAME] = $databaseUsername;
        }

        $databasePassword = $this->getDatabasePassword($env);
        if ($databasePassword) {
            $config[Config::KEY_DB_PASSWORD] = $databasePassword;
        }

        return $config;
    }

    /**
     * @param array $env
     * @param string $dnsPart
     * @return string|null
     */
    private function getDatabaseInfo(array $env, string $dnsPart): ?string
    {
        $dsnString = $env['db']['connection']['default']['host'] ?? '';
        return !empty($dsnString) ? DsnParts::getDsnPart($dsnString, $dnsPart) : null;
    }

    /**
     * @param array $env
     * @return string|null
     */
    private function getDatabaseUsername(array $env): ?string
    {
        return $env['db']['connection']['default']['username'] ?? null;
    }

    /**
     * @param array $env
     * @return string|null
     */
    private function getDatabasePassword(array $env): ?string
    {
        return $env['db']['connection']['default']['password'] ?? null;
    }

    /**
     * @param array $env
     * @return string|null
     */
    private function getDatabaseName(array $env): ?string
    {
        return $env['db']['connection']['default']['dbname'] ?? null;
    }

    /**
     * @return array
     */
    private function fetchEnvConfig(): array
    {
        $envFilePath = $this->getMagentoConfig($this->workingDirectory);
        $env = $envFilePath ? File::getRequire($envFilePath) : [];
        return is_array($env) ? $env : [];
    }

    /**
     * @param string $directoryPath
     * @return string|null
     */
    private function getMagentoConfig(string $directoryPath): ?string
    {
        $envFilePath = FilePath::buildPath([$directoryPath, 'app', 'etc', 'env.php']);
        $isFileExists = File::isFile($envFilePath);

        if (!$isFileExists
            && !HomeDirectory::isHomeDirectory($directoryPath)
            && !HomeDirectory::isRootDirectory($directoryPath)
        ) {
            return $this->getMagentoConfig(File::dirname($directoryPath));
        }

        return $isFileExists ? $envFilePath : null;
    }
}
