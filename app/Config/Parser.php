<?php

declare(strict_types = 1);

namespace App\Config;

use App\Config as AppConfig;
use App\Services\FilePath;
use Symfony\Component\Yaml\Yaml;

class Parser
{
    /**
     * @var \App\Config\Finder
     */
    private $configFinder;

    /**
     * @var \App\Config\Magento
     */
    private $magentoConfig;

    /**
     * @param string $workingDirectory
     */
    public function __construct(string $workingDirectory)
    {
        $this->configFinder = new Finder($workingDirectory);
        $this->magentoConfig = new Magento($workingDirectory);
    }

    /**
     * @return array
     */
    public function toConfigArray(): array
    {
        $config = Yaml::parseFile(__DIR__ . '/../../config/config.yml');

        $config = $this->applyConfigFiles($config);
        $config = $this->applyMagentoConfig($config);
        $config = $this->applyMagentoConfigFile($config);
        $config = $this->applyCurrentConfigFile($config);

        $magentoDirectory = $config[AppConfig::OPTION_MAGENTO_DIR] ?? null;
        if (!empty($magentoDirectory) && !$this->magentoConfig->getMagentoDirectory()) {
            $magentoDirectory = FilePath::getCurrentPath($magentoDirectory);
            $this->magentoConfig = new Magento($magentoDirectory);
            $config = $this->applyMagentoConfig($config);
            $config = $this->applyMagentoConfigFile($config);
            $config = $this->applyCurrentConfigFile($config);
        }

        if (!empty($this->magentoConfig->getMagentoDirectory())) {
            $config[AppConfig::OPTION_MAGENTO_DIR] = $this->magentoConfig->getMagentoDirectory();
        }

        return $config;
    }

    /**
     * @param array $config
     * @return array
     *
     * @noinspection SlowArrayOperationsInLoopInspection
     */
    private function applyConfigFiles(array $config): array
    {
        $configFiles = $this->configFinder->getGlobalFiles();
        foreach ($configFiles as $configFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($configFile));
        }
        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function applyMagentoConfig(array $config): array
    {
        return array_replace_recursive($config, $this->magentoConfig->toConfigArray());
    }

    /**
     * @param array $config
     * @return array
     */
    private function applyMagentoConfigFile(array $config): array
    {
        $magentoConfigFile = $this->configFinder->getCurrentFile($this->magentoConfig->getMagentoDirectory());
        if ($magentoConfigFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($magentoConfigFile));
        }
        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function applyCurrentConfigFile(array $config): array
    {
        $currentConfigFile = $this->configFinder->getCurrentFile();
        if ($currentConfigFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($currentConfigFile));
        }
        return $config;
    }
}
