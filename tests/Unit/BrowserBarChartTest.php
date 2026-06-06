<?php

use App\Support\Charts\BrowserBarChart;
use Tests\TestCase;

uses(TestCase::class);

test('browser bar chart rows normalize values and translated summaries', function () {
    $rows = BrowserBarChart::rows([
        ['key' => 'empty', 'label' => 'Empty', 'value' => 0],
        ['key' => 'small', 'label' => 'Small', 'value' => 5],
        ['key' => 'large', 'label' => 'Large', 'value' => 10, 'display_value' => '10 minutes'],
        ['key' => 'negative', 'label' => 'Negative', 'value' => -50],
    ], 'dashboard.foundation.chart.item_summary');

    expect($rows)->toHaveCount(4)
        ->and($rows[0])->toMatchArray([
            'key' => 'empty',
            'value' => 0,
            'display_value' => '0',
            'percent' => 4,
            'summary' => 'Empty chart value is 0.',
        ])
        ->and($rows[1]['percent'])->toBe(50)
        ->and($rows[2])->toMatchArray([
            'value' => 10,
            'display_value' => '10 minutes',
            'percent' => 100,
            'summary' => 'Large chart value is 10.',
        ])
        ->and($rows[3])->toMatchArray([
            'value' => 0,
            'display_value' => '0',
            'percent' => 4,
        ]);
});

test('browser bar chart returns an empty row set for empty charts', function () {
    expect(BrowserBarChart::rows([], 'dashboard.foundation.chart.item_summary'))->toBe([]);
});
