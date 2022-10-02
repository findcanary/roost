<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use App\Facades\AppConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class Dump
{
    /**
     * @return void
     */
    public static function initDumpDisk(): void
    {
        $filesystemConfig = [
            'filesystems.disks.dump.driver' => 'local',
            'filesystems.disks.dump.root' => static::getDatabaseDir(),
            'filesystems.disks.dump.disable_asserts' => true,
        ];
        config($filesystemConfig);
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public static function getDumpFilesystem(): FilesystemAdapter
    {
        $dumpDisk = Storage::disk('dump');
        if (!$dumpDisk instanceof FilesystemAdapter) {
            throw new \UnexpectedValueException('Dump disk should be a FilesystemAdapter');
        }
        return $dumpDisk;
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getDumpPath(string $file): string
    {
        if (str_starts_with($file, DIRECTORY_SEPARATOR)) {
            $dbPath = $file;
        } elseif (str_starts_with($file, '~')) {
            $dbPath = str_replace('~', Directory::getHomeDirectory(), $file);
        } elseif (str_starts_with($file, '.' . DIRECTORY_SEPARATOR)) {
            $dbPath = getcwd() . DIRECTORY_SEPARATOR . substr($file, 2);
        } else {
            $dbPath = static::getDatabaseDir() . DIRECTORY_SEPARATOR . $file;
        }

        return $dbPath;
    }

    /**
     * @return string
     */
    private static function getDatabaseDir(): string
    {
        $dumpDir = AppConfig::getConfigValue(Config::KEY_STORAGE);
        $dumpDir = !empty($dumpDir) ? $dumpDir : getcwd();
        $dumpDir = str_replace('~', Directory::getHomeDirectory(), $dumpDir);

        File::ensureDirectoryExists($dumpDir);
        return realpath($dumpDir);
    }

    /**
     * @param string $title
     * @return string|null
     *
     * @throws \PhpSchool\CliMenu\Exception\InvalidTerminalException
     */
    public static function getDumpName(string $title): ?string
    {
        $dumpItems = static::getDumpList();

        $menuOptions = array_map(static function ($dumpItem) {
            return sprintf(
                '%-50s %-15s %s',
                $dumpItem['name'],
                $dumpItem['size'],
                $dumpItem['date']
            );
        }, $dumpItems);

        return Menu::menu($title, $menuOptions);
    }

    /**
     * @return string[][]
     */
    private static function getDumpList(): array
    {
        $files = File::files(static::getDatabaseDir());
        $dumps = [];
        foreach ($files as $file) {
            if (!$file->isFile() || !DumpFile::isIncomeFileSupported($file->getFilename())) {
                continue;
            }

            $filename = $file->getFilename();
            $dumps[$filename] = [
                'name' => $filename,
                'size' => FormattedFileSize::getFormattedFileSize($file->getSize()),
                'date' => date('d M Y', $file->getCTime()),
            ];
        }
        return $dumps;
    }
}
