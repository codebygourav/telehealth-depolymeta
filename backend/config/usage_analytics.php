<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Usage Analytics Fake Data
    |--------------------------------------------------------------------------
    |
    | Leave this disabled to read real appointment analytics from the database.
    | Enable it, or provide a PHP config file path, to return fake analytics
    | without changing UsageAnalyticsController.
    |
    | The optional path should point to a PHP file that returns the same shape:
    | [
    |     'summary' => [...],
    |     'chart_data' => ['week' => [...], 'month' => [...], 'year' => [...]],
    | ]
    |
    */

    'fake_data' => [
        'enabled' => env('USAGE_ANALYTICS_FAKE_DATA_ENABLED', false),
        'path' => env('USAGE_ANALYTICS_FAKE_DATA_PATH'),

        'data' => [
            'summary' => [
                'current_month_count' => 48,
                'last_month_count' => 36,
                'percentage_change' => 33.3,
                'is_positive' => true,
            ],
            'chart_data' => [
                'week' => [
                    ['label' => 'Mon', 'date' => '2026-04-20', 'value' => 6],
                    ['label' => 'Tue', 'date' => '2026-04-21', 'value' => 8],
                    ['label' => 'Wed', 'date' => '2026-04-22', 'value' => 10],
                    ['label' => 'Thu', 'date' => '2026-04-23', 'value' => 7],
                    ['label' => 'Fri', 'date' => '2026-04-24', 'value' => 9],
                    ['label' => 'Sat', 'date' => '2026-04-25', 'value' => 5],
                    ['label' => 'Sun', 'date' => '2026-04-26', 'value' => 3],
                ],
                'month' => [
                    ['label' => 'Jan', 'date' => '2026-01', 'value' => 28],
                    ['label' => 'Feb', 'date' => '2026-02', 'value' => 32],
                    ['label' => 'Mar', 'date' => '2026-03', 'value' => 36],
                    ['label' => 'Apr', 'date' => '2026-04', 'value' => 48],
                    ['label' => 'May', 'date' => '2026-05', 'value' => 44],
                    ['label' => 'Jun', 'date' => '2026-06', 'value' => 52],
                    ['label' => 'Jul', 'date' => '2026-07', 'value' => 46],
                    ['label' => 'Aug', 'date' => '2026-08', 'value' => 58],
                    ['label' => 'Sep', 'date' => '2026-09', 'value' => 54],
                    ['label' => 'Oct', 'date' => '2026-10', 'value' => 61],
                    ['label' => 'Nov', 'date' => '2026-11', 'value' => 57],
                    ['label' => 'Dec', 'date' => '2026-12', 'value' => 64],
                ],
                'year' => [
                    ['label' => '2020', 'date' => '2020', 'value' => 180],
                    ['label' => '2021', 'date' => '2021', 'value' => 220],
                    ['label' => '2022', 'date' => '2022', 'value' => 265],
                    ['label' => '2023', 'date' => '2023', 'value' => 310],
                    ['label' => '2024', 'date' => '2024', 'value' => 380],
                    ['label' => '2025', 'date' => '2025', 'value' => 450],
                    ['label' => '2026', 'date' => '2026', 'value' => 528],
                ],
            ],
        ],
    ],
];
