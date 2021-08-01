<?php

declare(strict_types=1);

namespace App\Traits\Command;

trait Task
{
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
