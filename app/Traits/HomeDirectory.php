<?php

declare(strict_types = 1);

namespace App\Traits;

use Illuminate\Support\Facades\File;

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
    private function getHomeDirectory(): ?string
    {
        return env('HOME') ?: (env('HOMEDRIVE') . env('HOMEPATH'));
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function isRootDirectory(string $directoryPath): bool
    {
        return $this->getRootDirectory() === $directoryPath || File::dirname($directoryPath) === $directoryPath;
    }

    /**
     * @return string
     */
    private function getRootDirectory(): string
    {
        $pathInPieces = explode(DIRECTORY_SEPARATOR, __DIR__);
        $rootDir = $pathInPieces[0] ?? __DIR__;
        $rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $rootDir;
    }
}
