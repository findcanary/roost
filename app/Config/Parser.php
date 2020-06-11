<?php

declare(strict_types = 1);

namespace App\Config;

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
     * @noinspection SlowArrayOperationsInLoopInspection
     */
    public function toConfigArray(): array
    {
        $config = Yaml::parseFile(__DIR__ . '/../../config/config.yml');

        $configFiles = $this->configFinder->getGlobalFiles();
        foreach ($configFiles as $configFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($configFile));
        }

        $config = array_replace_recursive($config, $this->magentoConfig->toConfigArray());

        $magentoConfigFile = $this->configFinder->getCurrentFile($this->magentoConfig->getMagentoDirectory());
        if ($magentoConfigFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($magentoConfigFile));
        }

        $currentConfigFile = $this->configFinder->getCurrentFile();
        if ($currentConfigFile) {
            $config = array_replace_recursive($config, Yaml::parseFile($currentConfigFile));
        }

        return $config;
    }
}
