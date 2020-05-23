<?php

declare(strict_types = 1);

namespace App;

use LaravelZero\Framework\Commands\Command as BaseCommand;
use App\Config as AppConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    const OPTION_MAGENTO_DIR = 'magento-directory';

    /**
     * @var \App\Config
     */
    private $appConfig;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

    /**
     * @param string|null $configKey
     * @return array|string|null
     */
    protected function getConfigValue(string $configKey)
    {
        $this->ensureAppConfigInitialized();
        return $this->appConfig->getConfigValue($configKey);
    }

    /**
     * @param string $configKey
     * @param string $configValue
     * @return void
     */
    protected function setConfigValue(string $configKey, string $configValue): void
    {
        $this->ensureAppConfigInitialized();
        $this->appConfig->setConfigValue($configKey, $configValue);
    }

    /**
     * @return array
     */
    protected function toConfigArray(): array
    {
        $this->ensureAppConfigInitialized();
        $configData = $this->appConfig->toConfigArray();
        ksort($configData);
        return $configData;
    }

    /**
     * @return void
     */
    private function ensureAppConfigInitialized(): void
    {
        if ($this->appConfig === null) {
            $this->appConfig = new AppConfig($this->input);
        }
    }

    /**
     * Write a string as success output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function success($string, $verbosity = null)
    {
        $this->output->writeln($this->successText($string), $this->parseVerbosity($verbosity));
    }

    /**
     * @param string $string
     * @return string
     */
    protected function successText(string $string): string
    {
        return "<fg=green>$string</>";
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->output->writeln($this->errorText($string), $this->parseVerbosity($verbosity));
    }

    /**
     * @param string $string
     * @return string
     */
    protected function errorText(string $string): string
    {
        return "<fg=red>$string</>";
    }

    /**
     * @param string $title
     * @param callable|null $task
     * @return bool
     */
    public function task(string $title = '', $task = null): bool
    {
        $this->output->write("$title: <comment>processing...</comment>");

        $result = is_callable($task) ? $task() : $task;

        $errorMessage = null;
        if ($result === true || $result === null) {
            $resultText = $this->successText('✔');
            $result = true;
        } else {
            $resultText = $this->errorText('𐄂');
            $errorMessage = is_string($result) && !empty($result) ? $result : null;
            $result = false;
        }

        if ($this->output->isDecorated()) { // Determines if we can use escape sequences
            // Move the cursor to the beginning of the line
            $this->output->write("\x0D");

            // Erase the line
            $this->output->write("\x1B[2K");
        } else {
            $this->output->writeln(''); // Make sure we first close the previous line
        }

        $this->output->writeln("$title: " . $resultText);
        if (!empty($errorMessage)) {
            $this->error('Error: ' . $errorMessage);
        }
        return $result;
    }
}
