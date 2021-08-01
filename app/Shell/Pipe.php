<?php

declare(strict_types=1);

namespace App\Shell;

class Pipe
{
    /**
     * @var \App\Shell\CommandInterface[]
     */
    private $commands = [];

    /**
     * @param \App\Shell\CommandInterface $command
     * @return $this
     */
    public function command(CommandInterface $command): self
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * @param \App\Shell\CommandInterface[] $commands
     * @return $this
     */
    public function commands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->command($command);
        }
        return $this;
    }

    /**
     * @return \App\Shell\CommandInterface
     */
    public function getLastCommand(): ?CommandInterface
    {
        $commandsCount = count($this->commands);
        if ($commandsCount === 0) {
            return null;
        }

        $lastIndex = $commandsCount - 1;
        return $this->commands[$lastIndex];
    }

    /**
     * @param bool $includeEnvVar
     * @return array
     * @noinspection SlowArrayOperationsInLoopInspection
     */
    public function toParts(bool $includeEnvVar = false): array
    {
        $parts = [];

        $isFirstProceed = false;
        foreach ($this->commands as $command) {
            if (false === $isFirstProceed) {
                $isFirstProceed = true;

                $parts = array_merge($parts, $command->toParts($includeEnvVar));
                continue;
            }

            $parts = array_merge($parts, ['|'], $command->toParts($includeEnvVar));
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
     * @return void
     */
    public function passthru(): void
    {
        passthru($this->toString());
    }
}
