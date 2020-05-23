<?php

declare(strict_types = 1);

namespace App\Traits\Command;

trait OutputStyle
{
    /**
     * @param string $string
     * @return string
     */
    public function successText(string $string): string
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
    public function error($string, $verbosity = null): void
    {
        $this->output->writeln($this->errorText($string), $this->parseVerbosity($verbosity));
    }

    /**
     * @param string $string
     * @return string
     */
    public function errorText(string $string): string
    {
        return "<fg=red>$string</>";
    }
}
