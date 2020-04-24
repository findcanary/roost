<?php

declare(strict_types = 1);

namespace App;

use Symfony\Component\Console\Input\InputInterface;
use App\Config\Parser;
use App\Traits\FilePath;

class Config
{
    use FilePath;

    const FILENAME = '.dbm2.yml';

    const KEY_DUMP_DIR = 'dump-dir';

    const KEY_DB_HOST = 'db-host';
    const KEY_DB_PORT = 'db-port';
    const KEY_DB_NAME = 'db-name';
    const KEY_DB_USERNAME = 'db-username';
    const KEY_DB_PASSWORD = 'db-password';

    private const CONFIG_KEYS = [
        self::KEY_DUMP_DIR,
        self::KEY_DB_HOST,
        self::KEY_DB_PORT,
        self::KEY_DB_NAME,
        self::KEY_DB_PASSWORD,
        self::KEY_DB_USERNAME,
    ];

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var array
     */
    private $config;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @param string $configKey
     * @return array|string|null
     */
    public function getConfigValue(string $configKey)
    {
        $this->ensureAppConfigInitialized();
        $value = $this->config[$configKey] ?? null;
        return is_array($value) || null === $value ? $value : (string)$value;
    }

    /**
     * @param string $configKey
     * @param string $configValue
     * @return string|null
     */
    public function setConfigValue(string $configKey, string $configValue): ?string
    {
        $this->ensureAppConfigInitialized();
        $this->config[$configKey] = $configValue;
    }

    /**
     * @return array
     */
    public function toConfigArray(): array
    {
        $this->ensureAppConfigInitialized();
        return $this->config;
    }

    /**
     * @return void
     */
    private function ensureAppConfigInitialized(): void
    {
        if ($this->config === null) {
            $this->config = $this->initializeConfig();
        }
    }

    /**
     * @return array
     */
    private function initializeConfig(): array
    {
        $workingDirectory = $this->getCurrentPath($this->input->getOption(Command::OPTION_MAGENTO_DIR));
        $configParser = new Parser($workingDirectory);
        $configData = $configParser->toConfigArray();
        return $this->applyInputConfig($configData);
    }

    /**
     * @param array $configData
     * @return array
     */
    private function applyInputConfig(array $configData): array
    {
        foreach (self::CONFIG_KEYS as $configKey) {
            $value = $this->input->getOption($configKey);
            if ($value) {
                $configData[$configKey] = $value;
            }
        }

        return $configData;
    }
}
