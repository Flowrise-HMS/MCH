<?php

return [
    'name' => 'MCH',

    'book_units' => ['ANC', 'CWC'],

    'anc_returns' => [
        'timezone' => env('MCH_ANC_RETURN_TIMEZONE', env('APP_TIMEZONE', 'Africa/Accra')),
        'frequency' => 'weekly',
        'interval' => (int) env('MCH_ANC_RETURN_INTERVAL_WEEKS', 4),
        'occurrence_count' => (int) env('MCH_ANC_RETURN_OCCURRENCES', 8),
        'start_hour' => (int) env('MCH_ANC_RETURN_START_HOUR', 9),
        'start_minute' => (int) env('MCH_ANC_RETURN_START_MINUTE', 0),
        'duration_minutes' => (int) env('MCH_ANC_RETURN_DURATION_MINUTES', 30),
    ],
];
