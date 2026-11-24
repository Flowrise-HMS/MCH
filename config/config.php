<?php

return [
    'name' => 'MCH',

    'permissions' => [
        'mch_override_consent' => 'Override MCH Data Sharing Consent',
    ],

    'book_units' => ['ANC', 'CWC'],

    /*
     | Data-sharing consent (mch_records.data_consented) gates FHIR, API and AI
     | assistant access to MCH data; care workflows are never blocked.
     */
    'consent' => [
        'enforce' => (bool) env('MCH_ENFORCE_CONSENT', true),
    ],

    'anc_returns' => [
        'timezone' => env('MCH_ANC_RETURN_TIMEZONE', env('APP_TIMEZONE', 'Africa/Accra')),
        'frequency' => 'weekly',
        'interval' => (int) env('MCH_ANC_RETURN_INTERVAL_WEEKS', 4),
        'occurrence_count' => (int) env('MCH_ANC_RETURN_OCCURRENCES', 8),
        'start_hour' => (int) env('MCH_ANC_RETURN_START_HOUR', 9),
        'start_minute' => (int) env('MCH_ANC_RETURN_START_MINUTE', 0),
        'duration_minutes' => (int) env('MCH_ANC_RETURN_DURATION_MINUTES', 30),
    ],

    'epi_appointments' => [
        'start_hour' => (int) env('MCH_EPI_APPOINTMENT_START_HOUR', 9),
        'start_minute' => (int) env('MCH_EPI_APPOINTMENT_START_MINUTE', 0),
        'duration_minutes' => (int) env('MCH_EPI_APPOINTMENT_DURATION_MINUTES', 30),
    ],

    'epi_dues' => [
        /*
         * A scheduled dose whose schedule item has no maximum_age_days counts
         * as overdue once it is this many days past its due date.
         */
        'overdue_after_days' => (int) env('MCH_EPI_OVERDUE_AFTER_DAYS', 28),
    ],
];
