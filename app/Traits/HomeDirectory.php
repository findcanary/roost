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
        return env('HOME') === $directoryPath;
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function isInsideHomeDirectory(string $directoryPath): bool
    {
        return strpos($directoryPath, env('HOME')) === 0;
    }
}
