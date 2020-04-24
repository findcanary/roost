<?php

declare(strict_types = 1);

namespace App\Shell\Command;

class Pv extends Base
{
    /**
     * @return string
     */
    protected function name(): string
    {
        return 'pv';
    }
}
