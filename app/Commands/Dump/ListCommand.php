<?php

declare(strict_types=1);

namespace App\Commands\Dump;

use LaravelZero\Framework\Commands\Command;
use App\Traits\Command as AppCommand;
use App\Facades\AppConfig;
use App\Services\AwsS3;
use App\Services\FormattedFileSize;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class ListCommand extends Command
{
    use AppCommand;

    public const COMMAND = 'dump:list';

    /**
     * @var string
     */
    protected $signature = self::COMMAND
        . ' {search? : Search by dumpname}';

    /**
     * @var string
     */
    protected $description = 'Display list of dumps AWS';

    /**
     * @return void
     * @throws \League\Flysystem\FilesystemException
     */
    public function handle(): void
    {
        $search = $this->argument('search');

        AwsS3::initAwsBucket($this->output, false);

        $awsDriver = AwsS3::getAwsDisk();

        $files = $awsDriver->listContents((string)AppConfig::getConfigValue('project'), true);
        $files = $files->filter(static function (\League\Flysystem\StorageAttributes $file) {
            return $file->isFile();
        });

        if ($search) {
            $files = $files->filter(static function (\League\Flysystem\StorageAttributes $file) use ($search) {
                return str_contains(basename($file->path()), $search);
            });
        }

        $tables = [];
        foreach ($files as $file) {
            $basename = basename($file->path());
            $dirname = dirname($file->path());

            if (empty($tables[$dirname])) {
                $tables[$dirname] = [];
            }

            $tables[$dirname][] = [
                'name' => $basename,
                'size' => FormattedFileSize::getFormattedFileSize((float)$file->fileSize()),
                'date' => date('d M Y', $file->lastModified()),
            ];
        }

        foreach ($tables as $projectName => $tableData) {
            $projectTitle = $projectName ? 'Project: <comment>' . $projectName . '</comment>' : 'No Project';
            $this->info($projectTitle);
            $this->renderTable($tableData);
        }
    }

    /**
     * @param array $tableData
     * @return void
     */
    private function renderTable(array $tableData): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['File Name', 'Size', 'Changed Date']);
        $table->setRows($tableData);
        $table->setColumnWidths([40, 10, 0]);
        $table->setColumnStyle(1, (new TableStyle())->setPadType(STR_PAD_LEFT));
        $table->setColumnStyle(2, (new TableStyle())->setPadType(STR_PAD_LEFT));
        $table->setStyle('box');
        $table->render();
    }
}
