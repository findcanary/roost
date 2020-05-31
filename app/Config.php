<?php

declare(strict_types = 1);

namespace App;

use Symfony\Component\Console\Input\InputInterface;
use App\Config\Parser;
use App\Services\FilePath;

class Config
{
    const FILENAME = '.roost.yml';

    const OPTION_MAGENTO_DIR = 'magento-directory';

    const KEY_PROJECT = 'project';
    const KEY_STORAGE = 'storage';

    const KEY_DB_HOST = 'db-host';
    const KEY_DB_PORT = 'db-port';
    const KEY_DB_NAME = 'db-name';
    const KEY_DB_USERNAME = 'db-username';
    const KEY_DB_PASSWORD = 'db-password';

    const KEY_AWS_REGION     = 'aws-region';
    const KEY_AWS_BUCKET     = 'aws-bucket';
    const KEY_AWS_ACCESS_KEY = 'aws-access-key';
    const KEY_AWS_SECRET_KEY = 'aws-secret-key';

    private const CONFIG_KEYS = [
        self::KEY_PROJECT,
        self::KEY_STORAGE,
        self::KEY_DB_HOST,
        self::KEY_DB_PORT,
        self::KEY_DB_NAME,
        self::KEY_DB_PASSWORD,
        self::KEY_DB_USERNAME,
        self::KEY_AWS_REGION,
        self::KEY_AWS_BUCKET,
        self::KEY_AWS_ACCESS_KEY,
        self::KEY_AWS_SECRET_KEY,
    ];

    /**
     * @var array
     */
    private $config;

    /**
     * @param string $configKey
     * @return array|string|null
     */
    public function getConfigValue(string $configKey)
    {
        $value = $this->config[$configKey] ?? null;
        return is_array($value) || null === $value ? $value : (string)$value;
    }

    /**
     * @param string $configKey
     * @param string $configValue
     * @return void
     */
    public function setConfigValue(string $configKey, string $configValue): void
    {
        $this->config[$configKey] = $configValue;
    }

    /**
     * @return array
     */
    public function toConfigArray(): array
    {
        return $this->config;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return void
     */
    public function ensureAppConfigInitialized(InputInterface $input): void
    {
        if ($this->config === null) {
            $this->config = $this->initializeConfig($input);
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return array
     */
    private function initializeConfig(InputInterface $input): array
    {
        $workingDirectory = FilePath::getCurrentPath($input->getOption(self::OPTION_MAGENTO_DIR));
        $configParser = new Parser($workingDirectory);
        $configData = $configParser->toConfigArray();
        return $this->applyInputConfig($configData, $input);
    }

    /**
     * @param array $configData
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return array
     */
    private function applyInputConfig(array $configData, InputInterface $input): array
    {
        foreach (self::CONFIG_KEYS as $configKey) {
            $value = $input->getOption($configKey);
            if ($value) {
                $configData[$configKey] = $value;
            }
        }

        return $configData;
    }
}
