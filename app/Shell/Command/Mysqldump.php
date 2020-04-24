<?php

declare(strict_types = 1);

namespace App\Shell\Command;

class Mysqldump extends Base
{
    /**
     * @param array $envVars
     */
    public function __construct(array $envVars = [])
    {
        $this->arguments([
            '--single-transaction',
            '--quick'
        ]);

        parent::__construct($envVars);
    }

    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'mysqldump';
    }
}
