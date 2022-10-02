<?php

declare(strict_types=1);

namespace App\Services;

class FilePath
{
    /**
     * @param string[] $pathParts
     * @return string
     */
    public static function buildPath(array $pathParts): string
    {
        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }

    /**
     * @param string|null $path
     * @return string
     */
    public static function getCurrentPath(?string $path): string
    {
        if (null === $path) {
            $currentPath = getcwd();
        } elseif (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $currentPath = $path;
        } elseif (str_starts_with($path, '~')) {
            $currentPath = str_replace('~', Directory::getHomeDirectory(), $path);
        } else {
            $currentPath = getcwd() . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $currentPath;
    }
}
