<?php

declare(strict_types = 1);

namespace App\Services;

use App\Config;
use App\Facades\AppConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

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
     * @return \League\Flysystem\Filesystem
     */
    public static function getDumpDisk(): Filesystem
    {
        return Storage::disk('dump')->getDriver();
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getDumpPath(string $file): string
    {
        if (strpos($file, DIRECTORY_SEPARATOR) === 0) {
            $dbPath = $file;
        } elseif (strpos($file, '~') === 0) {
            $dbPath = str_replace('~', Directory::getHomeDirectory(), $file);
        } elseif (strpos($file, '.' . DIRECTORY_SEPARATOR) === 0) {
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
        $dumpDir = realpath($dumpDir);
        return $dumpDir;
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
