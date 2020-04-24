<?php

declare(strict_types = 1);

namespace App\Shell\Command;

class Mysql extends Base
{
    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'mysql';
    }
}
