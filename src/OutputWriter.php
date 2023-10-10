<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Keboola\Csv\CsvWriter;

class OutputWriter
{
    private const ORDER_COLUMNS = [
        'id',
        'ex_account_id',
        'fb_graph_node',
        'parent_id',
        'name',
        'key1',
        'key2',
        'ads_action_name',
        'action_type',
        'action_reaction',
        'value',
        'period',
        'end_time',
        'title',
    ];

    private const PRIMARY_KEYS = [
        'id',
        'parent_id',
        'key1',
        'key2',
        'end_time',
        'account_id',
        'campaign_id',
        'date_start',
        'date_stop',
        'ads_action_name',
        'action_type',
        'action_reaction',
    ];

    public function __construct(readonly ManifestManager $manifestManager, readonly string $outputDir)
    {
    }

    public function write(array $data): void
    {
        foreach ($data as $tableName => $tableData) {
            /** @var string[] $columns */
            $columns = $this->sortColumns($this->getColumnsFromData($tableData));
            if ($this->skipTable($columns)) {
                continue;
            }
            $table = new CsvWriter(sprintf('%s/%s.csv', $this->outputDir, $tableName));

            $outTableManifestOptions = new OutTableManifestOptions();
            $outTableManifestOptions
                ->setPrimaryKeyColumns($this->getPrimarKeys($columns))
                ->setColumns($columns);

            $this->manifestManager->writeTableManifest($tableName . '.csv', $outTableManifestOptions);

            foreach ($tableData as $tableRow) {
                $row = [];
                foreach ($columns as $column) {
                    $row[$column] = $tableRow[$column] ?? null;
                }
                $table->writeRow($row);
            }
        }
    }

    private function sortColumns(array $columns): array
    {
        $sortedColumns = [];
        foreach (self::ORDER_COLUMNS as $column) {
            if (!in_array($column, $columns)) {
                continue;
            }
            $sortedColumns[] = $column;
        }

        return array_merge($sortedColumns, array_diff($columns, $sortedColumns));
    }

    private function getPrimarKeys(array $arrayKeys): array
    {
        $primaryKeys = [];
        foreach (self::PRIMARY_KEYS as $primaryKey) {
            if (in_array($primaryKey, $arrayKeys)) {
                $primaryKeys[] = $primaryKey;
            }
        }

        return $primaryKeys;
    }

    private function skipTable(array $columns): bool
    {
        // unset system tables
        $diff = array_diff($columns, ['ex_account_id', 'fb_graph_node', 'parent_id', 'id']);
        return empty($diff);
    }

    private function getColumnsFromData(array $tableData): array
    {
        $columns = [];
        foreach ($tableData as $row) {
            $rowColumns = array_keys($row);
            $diffColumns = array_diff($rowColumns, $columns);
            $columns = array_merge($columns, $diffColumns);
        }

        return $columns;
    }
}
