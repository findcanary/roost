<?php

declare(strict_types=1);

namespace App\Shell\Command;

class Gunzip extends Base
{
    /**
     * @param array $envVars
     */
    public function __construct(array $envVars = [])
    {
        $this->arguments([
            '-cf'
        ]);

        parent::__construct($envVars);
    }

    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'gunzip';
    }
}
