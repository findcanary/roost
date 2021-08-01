<?php

declare(strict_types=1);

namespace App\Services;

class Progress
{
    /**
     * @return bool
     */
    public static function isPvAvailable(): bool
    {
        return !static::isWindows() && '' !== static::getPvLocation();
    }

    /**
     * @return string
     */
    private static function getPvLocation(): string
    {
        $out = null;
        $return = null;
        @exec('which ' . 'pv', $out, $return);
        return ($return === 0 && isset($out[0])) ? $out[0] : '';
    }

    /**
     * @return bool
     */
    private static function isWindows(): bool
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}
