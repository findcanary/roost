<?php

declare(strict_types = 1);

namespace App\Commands\Dump;

use App\Command;
use App\Traits\Command\AwsS3;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class ListCommand extends Command
{
    use AwsS3;

    const COMMAND = 'dump:list';

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
     */
    public function handle(): void
    {
        $search = $this->argument('search');

        $this->initAwsBucket(false);

        $awsDriver = $this->getAwsDisk();

        try {
            $files = $awsDriver->listContents((string)$this->getConfigValue('project'), true);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error($e->getAwsErrorMessage());
            return;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $files = array_filter($files, static function ($file) {
            return $file['type'] === 'file';
        });

        if ($search) {
            $files = array_filter($files, static function ($file) use ($search) {
                return strpos($file['basename'], $search) !== false;
            });
        }

        $tables = [];
        foreach ($files as $file) {
            if (empty($tables[$file['dirname']])) {
                $tables[$file['dirname']] = [];
            }

            $tables[$file['dirname']][] = [
                'name' => $file['basename'],
                'size' => $this->getFormattedFileSize((float)$file['size']),
                'date' => date('d M Y', $file['timestamp']),
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
