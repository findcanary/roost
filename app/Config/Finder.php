<?php

declare(strict_types = 1);

namespace App\Config;

use Illuminate\Support\Facades\File;
use App\Config as ConfigProvider;
use App\Traits\HomeDirectory;
use App\Traits\FilePath;

class Finder
{
    use HomeDirectory, FilePath;

    /**
     * @var string
     */
    private $workingDirectory;

    /**
     * @var array
     */
    private $globalFiles;

    /**
     * @param string $workingDirectory
     */
    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @return string
     */
    public function getCurrentFile(): ?string
    {
        $currentConfigFile = $this->buildPath([$this->workingDirectory, ConfigProvider::FILENAME]);
        return File::isFile($currentConfigFile) ? $currentConfigFile : null;
    }

    /**
     * @return array
     */
    public function getGlobalFiles(): array
    {
        if ($this->globalFiles === null) {
            $this->globalFiles = [];
            $this->findParentConfig($this->workingDirectory);
            $this->findHomeConfig();
        }
        return $this->globalFiles;
    }

    /**
     * @param string $directoryPath
     * @return void
     */
    private function findParentConfig(string $directoryPath): void
    {
        if (!$this->isHomeDirectory($directoryPath) && !$this->isRootDirectory($directoryPath)) {
            $parentDirectoryPath = File::dirname($directoryPath);
            $parentDirectoryConfig = $this->buildPath([$parentDirectoryPath, ConfigProvider::FILENAME]);
            if (File::isFile($parentDirectoryConfig)) {
                $this->globalFiles[] = $parentDirectoryConfig;
            }

            $this->findParentConfig($parentDirectoryPath);
        }
    }

    /**
     * @return void
     */
    private function findHomeConfig(): void
    {
        $homeConfig = $this->buildPath([$this->getHomeDirectory(), ConfigProvider::FILENAME]);
        if (File::isFile($homeConfig)) {
            $this->globalFiles[] = $homeConfig;
        }
    }
}
