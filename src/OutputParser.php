<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use Keboola\FacebookExtractor\Configuration\Node\RowConfig;

class OutputParser
{
    private const ADS_ACTION_STATS_ROW = [
        'actions',
        'properties',
        'conversion_values',
        'action_values',
        'canvas_component_avg_pct_view',
        'cost_per_10_sec_video_view',
        'cost_per_action_type',
        'cost_per_unique_action_type',
        'unique_actions',
        'video_10_sec_watched_actions',
        'video_15_sec_watched_actions',
        'video_30_sec_watched_actions',
        'video_avg_pct_watched_actions',
        'video_avg_percent_watched_actions',
        'video_avg_sec_watched_actions',
        'video_avg_time_watched_actions',
        'video_complete_watched_actions',
        'video_p100_watched_actions',
        'video_p25_watched_actions',
        'video_p50_watched_actions',
        'video_p75_watched_actions',
        'cost_per_conversion',
        'cost_per_outbound_click',
        'video_p95_watched_actions',
        'website_ctr',
        'website_purchase_roas',
        'purchase_roas',
        'outbound_clicks',
        'conversions',
        'video_play_actions',
        'video_thruplay_watched_actions',
    ];

    private const SERIALIZED_LISTS_TYPES = [
        'issues_info',
        'frequency_control_specs',
    ];

    public function __construct(readonly PageLoader $pageLoader, readonly ?string $pageId, readonly RowConfig $row)
    {
    }

    public function parseRow(array $response, string $fbGraphNode, string $parentId, ?string $tableName = null): array
    {
        $data = $response['data'] ?? [];

        $rowData = [];
        foreach ($data as $row) {
            $mainTableName = $this->getTableName($tableName ?? (string) $this->row->getQuery()->getPath());
            $tableData = [
                'ex_account_id' => $this->pageId,
                'fb_graph_node' => $fbGraphNode,
                'parent_id' => $parentId,
            ];
            $flatenData = [];
            array_walk($row, function ($value, $key) use (&$tableData, &$flatenData) {
                if (is_array($value) && array_key_exists('data', $value)) {
                    $flatenData['new_table'][(string) $key] = $value;
                } elseif (is_array($value)) {
                    $tableData = array_merge($tableData, $this->flattenArray($key, $value));
                } elseif (in_array($key, self::ADS_ACTION_STATS_ROW)) {
                    $flatenData['ads_actions'][$key] = $value;
                } elseif ($key === 'values') {
                    $flatenData['values'][$key] = $value;
                } elseif (in_array($key, self::SERIALIZED_LISTS_TYPES)) {
                    $tableData[$key] = json_encode($value);
                } else {
                    $tableData[$key] = $value;
                }
            });

            if (array_key_exists('values', $flatenData)) {
                $tableData = $this->parseValues($tableData, $flatenData['values']);
            }

            $rowData[$mainTableName][] = $tableData;
            if (array_key_exists('new_table', $flatenData)) {
                foreach ($flatenData['new_table'] as $newTableName => $table) {
                    $rowData = array_merge_recursive(
                        $rowData,
                        $this->parseRow(
                            $table,
                            sprintf(
                                '%s_%s',
                                $fbGraphNode,
                                $newTableName,
                            ),
                            $row['id'] ?? null,
                            (string) $newTableName,
                        ),
                    );
                }
            }
        }

        if (isset($response['paging']['next']) && isset($response['paging']['cursors']['after'])) {
            $nextPageResponse = $this->pageLoader->loadPageFromUrl($response['paging']['next']);

            $rowData = array_merge_recursive(
                $rowData,
                $this->parseRow(
                    $nextPageResponse->getContent() ?? [],
                    $fbGraphNode,
                    (string) $parentId,
                    $tableName,
                ),
            );
        }

        return $rowData;
    }

    private function parseValues(array $mainTable, array $values): array
    {
        $mainTable['key1'] = '';
        $mainTable['key2'] = '';

        if (array_key_exists('value', $values)) {
            $mainTable['value'] = $values['value'];
        }
        if (array_key_exists('end_time', $values)) {
            $mainTable['end_time'] = $values['end_time'];
        }

        return $mainTable;
    }

    private function flattenArray(string $parentKey, array $values): array
    {
        $tableData = [];
        foreach ($values as $childKey => $value) {
            $key = sprintf('%s_%s', $parentKey, $childKey);
            if (is_array($value)) {
                $tableData = array_merge($tableData, $this->flattenArray($key, $value));
            } else {
                $tableData[$key] = $value;
            }
        }
        return $tableData;
    }

    private function getTableName(string $tableName): string
    {
        $pattern = sprintf('/_%s$/', $tableName);
        $match = preg_match($pattern, $this->row->getName());
        if ($this->row->getName() === $tableName || $match === 1) {
            return $this->row->getName();
        }
        return sprintf('%s_%s', $this->row->getName(), $tableName);
    }
}
