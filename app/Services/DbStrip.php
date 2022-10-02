<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\TableGroup;
use App\Facades\AppConfig;

class DbStrip
{
    /**
     * @param string $tables
     * @param array $existingTables
     * @return array
     */
    public static function getTableList(string $tables, array $existingTables = []): array
    {
        $expandedTableList = static::expandTableList($tables);
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
    private static function expandTableList(string $tables): string
    {
        $tableDefinitions = explode(' ', $tables);

        foreach ($tableDefinitions as $idx => $table) {
            if (!static::looksLikeTableDefinition($table)) {
                continue;
            }

            $tableGroup = static::getTableDefinition($table);
            $tableDefinitions[$idx] = $tableGroup !== null ? implode(' ', $tableGroup->getTables()) : '';
        }

        $tableDefinitions = array_map('trim', $tableDefinitions);
        $tableDefinitionString = implode(' ', $tableDefinitions);

        return static::containsTableDefinition($tableDefinitionString)
            ? static::expandTableList($tableDefinitionString)
            : $tableDefinitionString;
    }

    /**
     * @param $string
     * @return bool
     */
    private static function looksLikeTableDefinition($string): bool
    {
        return str_starts_with($string, '@');
    }

    /**
     * @param $string
     * @return bool
     */
    private static function containsTableDefinition($string): bool
    {
        return str_contains($string, '@');
    }

    /**
     * @param $string
     * @return \App\Config\TableGroup|null
     */
    private static function getTableDefinition($string): ?TableGroup
    {
        $tableGroupId = substr($string, 1);

        $tableGroups = static::getTableGroups();
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
    private static function getTableGroups(): array
    {
        $tableGroupsConfig = AppConfig::getConfigValue('table-groups');
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
