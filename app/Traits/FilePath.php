<?php

declare(strict_types = 1);

namespace App\Traits;

trait FilePath
{
    use HomeDirectory;

    /**
     * @param string[] $pathParts
     * @return string
     */
    private function buildPath(array $pathParts): string
    {
        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }

    /**
     * @param string|null $path
     * @return string
     */
    private function getCurrentPath(?string $path): string
    {
        if (null === $path) {
            $currentPath = getcwd();
        } elseif (strpos($path, DIRECTORY_SEPARATOR) === 0) {
            $currentPath = $path;
        } elseif (strpos($path, '~') === 0) {
            $currentPath = str_replace('~', $this->getHomeDirectory(), $path);
        } else {
            $currentPath = getcwd() . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $currentPath;
    }
}
