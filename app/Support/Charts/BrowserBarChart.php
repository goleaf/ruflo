<?php

namespace App\Support\Charts;

final class BrowserBarChart
{
    private const int MINIMUM_VISIBLE_PERCENT = 4;

    /**
     * @param  list<array{key: string, label: string, value: int, display_value?: string}>  $items
     * @return list<array{key: string, label: string, value: int, display_value: string, percent: int, summary: string}>
     */
    public static function rows(array $items, string $summaryKey): array
    {
        if ($items === []) {
            return [];
        }

        $max = max(1, ...array_map(fn (array $item): int => max(0, $item['value']), $items));

        return array_map(fn (array $item): array => [
            'key' => $item['key'],
            'label' => $item['label'],
            'value' => max(0, $item['value']),
            'display_value' => $item['display_value'] ?? (string) max(0, $item['value']),
            'percent' => (int) max(self::MINIMUM_VISIBLE_PERCENT, round((max(0, $item['value']) / $max) * 100)),
            'summary' => __($summaryKey, [
                'label' => $item['label'],
                'value' => max(0, $item['value']),
            ]),
        ], $items);
    }
}
