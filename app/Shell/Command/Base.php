<?php

declare(strict_types = 1);

namespace App\Shell\Command;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class Base implements \App\Shell\CommandInterface
{
    /**
     * @var string[]
     */
    private $envVars;

    /**
     * @var string[]
     */
    private $arguments = [];

    /**
     * @var string|null
     */
    private $outputFile;

    /**
     * @var bool
     */
    private $outputAdd = false;

    /**
     * @param array $envVars
     */
    public function __construct(array $envVars = [])
    {
        $this->envVars = $envVars;
    }

    /**
     * The name of the command to be executed.
     *
     * @return string
     */
    abstract protected function name(): string;

    /**
     * @param array $args
     * @return $this
     */
    public function arguments(array $args): self
    {
        foreach ($args as $arg) {
            $this->argument($arg);
        }
        return $this;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function argument(string $arg): self
    {
        $this->arguments[] = $arg;
        return $this;
    }

    /**
     * @param string $outputFile
     * @param bool $outputAdd
     * @return $this
     */
    public function output(string $outputFile, bool $outputAdd = false): self
    {
        $this->outputFile = $outputFile;
        $this->outputAdd = $outputAdd;
        return $this;
    }

    /**
     * @param bool $includeEnvVar
     * @return array
     */
    public function toParts(bool $includeEnvVar = false): array
    {
        $parts = [$this->name()];

        $parts = array_merge($parts, $this->arguments);

        if ($this->outputFile) {
            $parts[] = $this->outputAdd ? '>>' : '>';
            $parts[] = $this->outputFile;
        }

        if ($includeEnvVar) {
            $parts = $this->addEnvVarsToParts($parts);
        }
        return $parts;
    }

    /**
     * @param array $parts
     * @return array
     */
    private function addEnvVarsToParts(array $parts): array
    {
        if (!empty($this->envVars)) {
            $envParts = [];
            foreach ($this->envVars as $name => $value) {
                $envParts[] = $name . '=' . $value;
            }
            array_unshift($parts, implode(' ', $envParts));
        }

        return $parts;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(' ', $this->toParts(true));
    }

    /**
     * @return string
     */
    public function run(): string
    {
        $process = $this->toProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * @return \Symfony\Component\Process\Process
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function toProcess(): Process
    {
        return new Process($this->toParts(), null, $this->envVars, null, 60 * 60 * 12);
    }

    /**
     * @return void
     */
    public function passthru(): void
    {
        passthru($this->toString());
    }
}
