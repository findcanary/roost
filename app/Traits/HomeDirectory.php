<?php

declare(strict_types = 1);

namespace App\Traits;

trait HomeDirectory
{
    /**
     * @param string $directoryPath
     * @return bool
     */
    private function isHomeDirectory(string $directoryPath): bool
    {
        return $this->getHomeDirectory() === $directoryPath;
    }

    /**
     * @return string
     */
    private function getHomeDirectory(): string
    {
        return env('HOME');
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function isRootDirectory(string $directoryPath): bool
    {
        return $this->getRootDirectory() === $directoryPath;
    }

    /**
     * @return string
     */
    private function getRootDirectory(): string
    {
        $pathInPieces = explode(DIRECTORY_SEPARATOR, __DIR__);
        $rootDir = $pathInPieces[0] ?? __DIR__;
        $rootDir = $rootDir === '' ? DIRECTORY_SEPARATOR : $rootDir;
        return $rootDir;
    }
}
