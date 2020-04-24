<?php

declare(strict_types = 1);

namespace App\Shell\Command;

class Sed extends Base
{
    /**
     * @return string
     */
    protected function name(): string
    {
        return 'sed';
    }
}
