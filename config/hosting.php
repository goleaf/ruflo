<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Restricted Hosting Mode
    |--------------------------------------------------------------------------
    |
    | RuFlo targets shared hosting where normal usage cannot depend on SSH,
    | cron, queue workers, supervisors, shell scripts, or Artisan access.
    |
    */

    'restricted' => (bool) env('RUFLO_RESTRICTED_HOSTING', true),

    'forbidden_runtime_dependencies' => [
        'artisan',
        'cron',
        'queue-worker',
        'shell',
        'supervisor',
    ],

    'web_processing' => [
        'chunk_size' => (int) env('RUFLO_WEB_PROCESSING_CHUNK_SIZE', 25),
        'max_runtime_seconds' => (int) env('RUFLO_WEB_PROCESSING_MAX_RUNTIME_SECONDS', 8),
        'retry_cooldown_seconds' => (int) env('RUFLO_WEB_PROCESSING_RETRY_COOLDOWN_SECONDS', 30),
        'resume_after_failure' => (bool) env('RUFLO_WEB_PROCESSING_RESUME_AFTER_FAILURE', true),
        'detail_limit' => (int) env('RUFLO_WEB_PROCESSING_DETAIL_LIMIT', 10),
    ],
];
