<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use App\Config\TableGroup;

trait DbStrip
{
    /**
     * @param string $tables
     * @param array $existingTables
     * @return array
     */
    private function getTableList(string $tables, array $existingTables = []): array
    {
        $expandedTableList = $this->expandTableList($tables);
        $tablePatterns = !empty($expandedTableList) ? explode(' ', $expandedTableList) : [];

        sort($tablePatterns);
        sort($existingTables);

        $matchedTables = [];

        foreach ($tablePatterns as $tablePattern) {
            $tablePattern = str_replace('*', '.*', $tablePattern);
            $tablePattern = '/^' . $tablePattern . '$/';

            foreach (preg_grep($tablePattern, $existingTables) as $matchedTable) {
                $matchedTables[] = $matchedTable;
            }
        }

        return $matchedTables;
    }

    /**
     * @param string $tables
     * @return string
     */
    private function expandTableList(string $tables): string
    {
        $tableDefinitions = explode(' ', $tables);

        foreach ($tableDefinitions as $idx => $table) {
            if (!$this->looksLikeTableDefinition($table)) {
                continue;
            }

            $tableGroup = $this->getTableDefinition($table);
            $tableDefinitions[$idx] = $tableGroup !== null ? implode(' ', $tableGroup->getTables()) : '';
        }

        $tableDefinitions = array_map('trim', $tableDefinitions);
        $tableDefinitionString = implode(' ', $tableDefinitions);

        return $this->containsTableDefinition($tableDefinitionString)
            ? $this->expandTableList($tableDefinitionString)
            : $tableDefinitionString;
    }

    /**
     * @param $string
     * @return bool
     */
    private function looksLikeTableDefinition($string): bool
    {
        return 0 === strpos($string, '@');
    }

    /**
     * @param $string
     * @return bool
     */
    private function containsTableDefinition($string): bool
    {
        return strpos($string, '@') !== false;
    }

    /**
     * @param $string
     * @return \App\Config\TableGroup|null
     */
    private function getTableDefinition($string): ?\App\Config\TableGroup
    {
        $tableGroupId = substr($string, 1);

        $tableGroups = $this->getTableGroups();
        foreach ($tableGroups as $tableGroup) {
            if ($tableGroup->getId() === $tableGroupId) {
                return $tableGroup;
            }
        }

        return null;
    }

    /**
     * @return TableGroup[]
     */
    private function getTableGroups(): array
    {
        $tableGroupsConfig = $this->getConfigValue('table-groups');
        $tableGroups = [];

        if (!empty($tableGroupsConfig) && is_array($tableGroupsConfig)) {
            foreach ($tableGroupsConfig as $singleTableGroupConfig) {
                $id = $singleTableGroupConfig['id'] ?? null;
                $description = $singleTableGroupConfig['description'] ?? null;
                $tables = $singleTableGroupConfig['tables'] ?? null;

                if ($id === null) {
                    throw new \RuntimeException('Expected table group to have an id');
                }

                if ($description === null) {
                    throw new \RuntimeException('Expected table group to have a description');
                }

                if ($tables === null) {
                    throw new \RuntimeException('Expected table group to have tables');
                }

                $tableGroups[] = new TableGroup($id, $description, $tables);
            }
        }

        return $tableGroups;
    }
}
