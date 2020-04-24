<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use Illuminate\Support\Facades\File;
use App\Config as AppConfig;

trait Dump
{
    /**
     * @param string $file
     * @return string
     */
    private function getDumpPath(string $file): string
    {
        if (strpos($file, '/') === 0) {
            $dbPath = $file;
        } elseif (strpos($file, './') === 0) {
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
        $dumpDir = $this->getConfigValue(AppConfig::KEY_DUMP_DIR);
        $dumpDir = !empty($dumpDir) ? $dumpDir : getcwd();
        $dumpDir = str_replace('~', env('HOME'), $dumpDir);

        File::ensureDirectoryExists($dumpDir);
        $dumpDir = realpath($dumpDir);
        return $dumpDir;
    }

    /**
     * @return string[]
     */
    private function getDumpList(): array
    {
        /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
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
