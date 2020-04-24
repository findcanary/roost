<?php

declare(strict_types = 1);

namespace App\Shell\Command;

class Unzip extends Base
{
    /**
     * @param array $envVars
     */
    public function __construct(array $envVars = [])
    {
        $this->arguments([
            '-p'
        ]);

        parent::__construct($envVars);
    }

    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'unzip';
    }
}
