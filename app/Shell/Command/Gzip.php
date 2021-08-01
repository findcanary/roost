<?php

declare(strict_types=1);

namespace App\Shell\Command;

class Gzip extends Base
{
    /**
     * @return string
     */
    protected function name(): string
    {
        return 'gzip';
    }
}
