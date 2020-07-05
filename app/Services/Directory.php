<?php

declare(strict_types = 1);

namespace App\Services;

use App\Facades\AppConfig;
use Illuminate\Support\Facades\File;

class Directory
{
    /**
     * @param string $directoryPath
     * @return bool
     */
    public static function isHomeDirectory(string $directoryPath): bool
    {
        return static::getHomeDirectory() === $directoryPath;
    }

    /**
     * @return string
     */
    public static function getHomeDirectory(): ?string
    {
        return env('HOME') ?: '/';
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    public static function isRootDirectory(string $directoryPath): bool
    {
        return static::getRootDirectory() === $directoryPath || File::dirname($directoryPath) === $directoryPath;
    }

    /**
     * @return string
     */
    public static function getRootDirectory(): string
    {
        $pathInPieces = explode(DIRECTORY_SEPARATOR, __DIR__);
        $rootDir = $pathInPieces[0] ?? __DIR__;
        $rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $rootDir;
    }

    /**
     * @return string
     */
    public static function getTmpDirectory(): string
    {
        $tmpDirectory = AppConfig::getConfigValue('tmp') ?: env('TMPDIR');
        return $tmpDirectory ?: getcwd();
    }
}
