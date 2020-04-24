<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use App\Shell\Command\Pv;

trait Progress
{
    /**
     * @return bool
     */
    private function isPvAvailable(): bool
    {
        return !$this->isWindows() && '' !== $this->getPvLocation();
    }

    /**
     * @return string
     */
    private function getPvLocation(): string
    {
        $out = null;
        $return = null;
        @exec('which ' . 'pv', $out, $return);
        return ($return === 0 && isset($out[0])) ? $out[0] : '';
    }

    /**
     * @return bool
     */
    private function isWindows(): bool
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}
