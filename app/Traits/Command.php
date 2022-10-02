<?php

declare(strict_types=1);

namespace App\Traits;

use App\Facades\AppConfig;
use App\Traits\Command\Task;
use App\Traits\Command\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait Command
{
    use OutputStyle;
    use Task;

    /**
     * @return void
     */
    protected function configureUsingFluentDefinition(): void
    {
        $this->signature .=
            ' {--m|magento-directory= : Magento root directory}'
            . ' {--p|project= : Project key}'
            . ' {--db-host= : Database host}'
            . ' {--db-port= : Database port}'
            . ' {--d|db-name= : Database name}'
            . ' {--db-username= : Database username}'
            . ' {--db-password= : Database password}'
            . ' {--g|storage= : Local DBs storage folder}'
            . ' {--b|aws-bucket= : AWS bucket name}'
            . ' {--aws-access-key= : AWS access key}'
            . ' {--aws-secret-key= : AWS secret key}'
            . ' {--aws-region= : AWS region}';

        parent::configureUsingFluentDefinition();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        AppConfig::ensureAppConfigInitialized($input);

        try {
            return parent::execute($input, $output);
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            $this->error($e->getProcess()->getErrorOutput());
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error(($e->getAwsErrorMessage() ?: $e->getMessage()));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return 0;
    }
}
