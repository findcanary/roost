<?php

declare(strict_types=1);

namespace App\Services;

class DsnParts
{
    /**
     * @param string $dsnString
     * @param string $part
     * @return string|null
     */
    public static function getDsnPart(string $dsnString, string $part): ?string
    {
        $colonPosition = strrpos($dsnString, ':');

        if ($colonPosition === false) {
            $host = $dsnString;
            $port = '';
        } else {
            $host = substr($dsnString, 0, $colonPosition);
            $port = substr($dsnString, $colonPosition + 1);
        }

        $dsnParts = ['host' => $host, 'port' => $port];
        return $dsnParts[$part] ?? null;
    }
}
