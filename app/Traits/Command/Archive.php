<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use App\Shell\Command\Gunzip;
use App\Shell\Command\Gzip;
use App\Shell\Command\Unzip;
use Illuminate\Support\Facades\File;

trait Archive
{
    /**
     * @var array
     */
    private $supportedArchives = [
        'gz',
        'zip'
    ];

    /**
     * @param string $filename
     * @return bool
     */
    private function isArchive(string $filename): bool
    {
        return in_array(File::extension($filename), $this->supportedArchives, true);
    }

    /**
     * @param string $filename
     * @param \App\Shell\Pipe $pipe
     * @return bool
     */
    private function addArchiveCommand(string $filename, \App\Shell\Pipe $pipe): bool
    {
        $fileType = File::extension($filename);
        switch ($fileType) {
            case 'gz':
                $pipe->command((new Gzip)->arguments(['-9', '--force']));
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
    private function addUnarchiveCommand(string $filePath, \App\Shell\Pipe $pipe): bool
    {
        $fileType = File::extension($filePath);
        switch ($fileType) {
            case 'zip':
                $pipe->command(
                    (new Unzip)->argument($filePath)
                );
                $isAdded = true;
                break;
            case 'gz':
                $pipe->command(
                    (new Gunzip)->argument($filePath)
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
