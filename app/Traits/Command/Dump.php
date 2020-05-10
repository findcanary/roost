<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use App\Config as AppConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

trait Dump
{
    /**
     * @return void
     */
    private function initDumpDisk(): void
    {
        $filesystemConfig = [
            'filesystems.disks.dump.driver' => 'local',
            'filesystems.disks.dump.root' => $this->getDatabaseDir(),
            'filesystems.disks.dump.disable_asserts' => true,
        ];
        config($filesystemConfig);
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    private function getDumpDisk(): Filesystem
    {
        return Storage::disk('dump')->getDriver();
    }

    /**
     * @param string $file
     * @return string
     */
    private function getDumpPath(string $file): string
    {
        if (strpos($file, DIRECTORY_SEPARATOR) === 0) {
            $dbPath = $file;
        } elseif (strpos($file, '~') === 0) {
            $dbPath = str_replace('~', env('HOME'), $file);
        } elseif (strpos($file, '.' . DIRECTORY_SEPARATOR) === 0) {
            $dbPath = getcwd() . DIRECTORY_SEPARATOR . substr($file, 2);
        } else {
            $dbPath = $this->getDatabaseDir() . DIRECTORY_SEPARATOR . $file;
        }

        return $dbPath;
    }

    /**
     * @return string
     */
    private function getDatabaseDir(): string
    {
        $dumpDir = $this->getConfigValue(AppConfig::KEY_STORAGE);
        $dumpDir = !empty($dumpDir) ? $dumpDir : getcwd();
        $dumpDir = str_replace('~', env('HOME'), $dumpDir);

        File::ensureDirectoryExists($dumpDir);
        $dumpDir = realpath($dumpDir);
        return $dumpDir;
    }

    /**
     * @param string $title
     * @return string|null
     */
    private function getDumpName(string $title): ?string
    {
        $dumpItems = $this->getDumpList();

        $menuOptions = array_map(static function ($dumpItem) {
            return sprintf(
                '%-50s %-15s %s',
                $dumpItem['name'],
                $dumpItem['size'],
                $dumpItem['date']
            );
        }, $dumpItems);

        return $this->menu($title, $menuOptions);
    }

    /**
     * @return string[][]
     */
    private function getDumpList(): array
    {
        $files = File::files($this->getDatabaseDir());
        $dumps = [];
        foreach ($files as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), $this->supportedIncomeFiles, true)) {
                continue;
            }

            $filename = $file->getFilename();
            $dumps[$filename] = [
                'name' => $filename,
                'size' => $this->getFormattedFileSize($file->getSize()),
                'date' => date('d M Y', $file->getCTime()),
            ];
        }
        return $dumps;
    }
}
