<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Traits\FormattedFileSize;

trait AwsS3
{
    use FormattedFileSize;

    /**
     * @param bool $initProgress
     * @return void
     */
    private function initAwsBucket(bool $initProgress = true): void
    {
        $filesystemConfig = [
            'filesystems.disks.aws.driver' => 's3',
            'filesystems.disks.aws.bucket' => $this->getConfigValue('aws-bucket'),
            'filesystems.disks.aws.key' => $this->getConfigValue('aws-access-key'),
            'filesystems.disks.aws.secret' => $this->getConfigValue('aws-secret-key'),
            'filesystems.disks.aws.disable_asserts' => true
        ];

        $region = $this->getConfigValue('aws-region');
        if ($region) {
            $filesystemConfig['filesystems.disks.aws.region'] = $region;
        }

        if ($initProgress) {
            $progressBar = null;

            /** @var \App\Command $this */
            $filesystemConfig['filesystems.disks.aws.options.@http.progress'] = function ($totalDownload, $sizeDownload, $totalUpload, $sizeUpload) use (&$progressBar) {
                /* Download */
                if ($progressBar === null && $totalDownload > 0 && $sizeDownload === 0) {
                    $progressBar = new ProgressBar($this->output);
                    $progressBar->setMaxSteps($totalDownload);
                    $progressBar->setFormat('Progress: %current_size:9s%/%max_size% [%bar%] %percent:3s%%  ETA: %remaining:6s%');
                    return;
                }

                if ($progressBar instanceof ProgressBar && $sizeDownload > 0) {
                    $progressBar->setProgress($sizeDownload);
                }

                if ($progressBar instanceof ProgressBar && $totalDownload > 0 && $sizeDownload === $totalDownload) {
                    $progressBar->finish();
                    $progressBar = null;
                    $this->output->writeln('');
                }

                /* Upload */
                if ($progressBar === null && $totalUpload > 0 && $sizeUpload === 0) {
                    $progressBar = new ProgressBar($this->output);
                    $progressBar->setMaxSteps($totalUpload);
                    $progressBar->setFormat('Progress: %current_size:9s%/%max_size% [%bar%] %percent:3s%%  ETA: %remaining:6s%');
                    return;
                }

                if ($progressBar instanceof ProgressBar && $sizeUpload > 0) {
                    $progressBar->setProgress($sizeUpload);
                }

                if ($progressBar instanceof ProgressBar && $sizeUpload > 0 && $sizeUpload === $totalUpload) {
                    $progressBar->finish();
                    $progressBar = null;
                    $this->output->writeln('');
                }
            };
        }

        config($filesystemConfig);
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    private function getAwsDisk(): Filesystem
    {
        return Storage::disk('aws')->getDriver();
    }

    /**
     * @param string $title
     * @param string|null $project
     * @return string|null
     */
    private function getAwsDumpFile(string $title, ?string $project = null): ?string
    {
        $dumpItems = $this->getAwsDumpList();

        $menuOptions = [];
        foreach ($dumpItems as $dumpFolder => $dumpFiles) {
            if (empty($menuOptions[$dumpFolder])) {
                $menuOptions[$dumpFolder] = [];
            }
            foreach ($dumpFiles as $filePath => $fileData) {
                $menuOptions[$dumpFolder][$filePath] = sprintf(
                    '%-50s %-15s %s',
                    $fileData['name'],
                    $fileData['size'],
                    $fileData['date']
                );
            }
        }

        $rootFiles = $menuOptions[''] ?? [];
        unset($menuOptions['']);
        $menuOptions = array_merge($menuOptions, $rootFiles);

        if (!empty($project) && !isset($dumpItems[$project])) {
            throw new \UnexpectedValueException(sprintf('Project <info>%s</info> is not found.', $project));
        }

        $menuOptions = !empty($project) ? $menuOptions[$project] : $menuOptions;
        return $this->menu($title, $menuOptions);
    }

    /**
     * @param string|null $project
     * @return array
     */
    private function getAwsDumpList(?string $project = null): array
    {
        $awsDisk = Storage::disk('aws')->getDriver();
        $dumpItems = $awsDisk->listContents('', true);

        $dumps = [];
        foreach ($dumpItems as $dumpItem) {
            if ($dumpItem['type'] === 'dir' && empty($dumps[$dumpItem['basename']])) {
                $dumps[$dumpItem['basename']] = [];
                continue;
            }

            $dumps[$dumpItem['dirname']][$dumpItem['path']] = [
                'path' => $dumpItem['path'],
                'name' => $dumpItem['basename'],
                'size' => $this->getFormattedFileSize((float)$dumpItem['size']),
                'date' => date('d M Y', $dumpItem['timestamp']),
                'timestamp' => $dumpItem['timestamp'],
            ];
        }

        if (!empty($project) && !isset($dumps[$project])) {
            throw new \UnexpectedValueException(sprintf('Project <info>%s</info> is not found.', $project));
        }

        return empty($project) ? $dumps : $dumps[$project];
    }
}
