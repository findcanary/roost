<?php

declare(strict_types = 1);

namespace App\Services;

use Illuminate\Support\Facades\File;

class DumpFile
{
    /**
     * @var array
     */
    private static $supportedIncomeFiles = ['gz', 'zip', 'sql', 'dump'];

    /**
     * @var array
     */
    private static $supportedOutcomeFiles = ['gz', 'sql'];

    /**
     * @param string $filename
     * @return bool
     */
    public static function isIncomeFileSupported(string $filename): bool
    {
        return in_array(File::extension($filename), static::$supportedIncomeFiles, true);
    }

    /**
     * @param string $filename
     * @return bool
     */
    public static function isOutcomeFileSupported(string $filename): bool
    {
        return in_array(File::extension($filename), static::$supportedOutcomeFiles, true);
    }

}
