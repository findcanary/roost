<?php

declare(strict_types=1);

namespace App\Config;

class TableGroup
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string[]
     */
    private $tables;

    /**
     * @param string $id
     * @param string $description
     * @param string $tables
     */
    public function __construct(string $id, string $description, string $tables)
    {
        $this->id = $id;
        $this->description = $description;
        $this->tables = $this->processTablesString($tables);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @param string $tablesString
     * @return string[]
     */
    private function processTablesString(string $tablesString): array
    {
        $tablesString = preg_replace('/\s/', ' ', $tablesString);
        $tablesString = preg_replace('/\s{2,}/', ' ', $tablesString);
        $tables = explode(' ', $tablesString);
        $tables = array_filter($tables);

        return $tables;
    }
}
