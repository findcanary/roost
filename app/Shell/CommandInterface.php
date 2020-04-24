<?php

declare(strict_types = 1);

namespace App\Shell;

interface CommandInterface
{
    /**
     * @return string
     */
    public function run(): string;

    /**
     * @param bool $includeEnvVar
     * @return array
     */
    public function toParts(bool $includeEnvVar = false): array;

    /**
     * @return void
     */
    public function passthru(): void;

    /**
     * @return string
     */
    public function toString(): string;
}
