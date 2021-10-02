<?php

declare(strict_types=1);

namespace App\Shell\Command;

class Warden extends Base
{
    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'warden';
    }
}
