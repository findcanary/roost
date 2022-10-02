<?php

declare(strict_types=1);

namespace App\Services;

use App\Shell\Command\Gunzip;
use App\Shell\Command\Gzip;
use App\Shell\Command\Unzip;
use Illuminate\Support\Facades\File;

class Archive
{
    /**
     * @var array
     */
    private static $supportedArchives = [
        'gz',
        'zip'
    ];

    /**
     * @param string $filename
     * @return bool
     */
    public static function isArchive(string $filename): bool
    {
        return in_array(File::extension($filename), static::$supportedArchives, true);
    }

    /**
     * @param string $filename
     * @param \App\Shell\Pipe $pipe
     * @return bool
     */
    public static function addArchiveCommand(string $filename, \App\Shell\Pipe $pipe): bool
    {
        $fileType = File::extension($filename);
        switch ($fileType) {
            case 'gz':
                $pipe->command((new Gzip())->arguments(['-9', '--force']));
                $isAdded = true;
                break;
            case 'sql':
            default:
                $isAdded = false;
                break;
        }

        return $isAdded;
    }

    /**
     * @param string $filePath
     * @param \App\Shell\Pipe $pipe
     * @return bool
     */
    public static function addUnarchiveCommand(string $filePath, \App\Shell\Pipe $pipe): bool
    {
        $fileType = File::extension($filePath);
        switch ($fileType) {
            case 'zip':
                $pipe->command(
                    (new Unzip())->argument($filePath)
                );
                $isAdded = true;
                break;
            case 'gz':
                $pipe->command(
                    (new Gunzip())->argument($filePath)
                );
                $isAdded = true;
                break;
            case 'sql':
            default:
                $isAdded = false;
                break;
        }

        return $isAdded;
    }
}
