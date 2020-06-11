<?php

declare(strict_types = 1);

namespace App\Config;

use Illuminate\Support\Facades\File;
use App\Config as ConfigProvider;
use App\Services\FilePath;
use App\Services\Directory;

class Finder
{
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
     * @param string|null $currentDirectory
     * @return string
     */
    public function getCurrentFile(string $currentDirectory = null): ?string
    {
        $directory = $currentDirectory ?: $this->workingDirectory;
        $currentConfigFile = FilePath::buildPath([$directory, ConfigProvider::FILENAME]);
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
        if (!Directory::isHomeDirectory($directoryPath) && !Directory::isRootDirectory($directoryPath)) {
            $parentDirectoryPath = File::dirname($directoryPath);
            $parentDirectoryConfig = FilePath::buildPath([$parentDirectoryPath, ConfigProvider::FILENAME]);
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
        $homeConfig = FilePath::buildPath([Directory::getHomeDirectory(), ConfigProvider::FILENAME]);
        if (File::isFile($homeConfig)) {
            $this->globalFiles[] = $homeConfig;
        }
    }
}
