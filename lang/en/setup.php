<?php

return [
    'navigation' => 'Setup',

    'pages' => [
        'status' => [
            'title' => 'Setup status',
            'heading' => 'Setup status',
            'subheading' => 'Review deployment readiness without exposing a public installer.',
        ],
    ],

    'checks' => [
        'app_key' => 'Application key',
        'app_url' => 'Application URL',
        'database' => 'Database connection',
        'migrations_table' => 'Migrations table',
        'pending_migrations' => 'Pending migrations',
        'queue_connection' => 'Queue connection',
        'restricted_hosting' => 'Restricted hosting mode',
        'storage_writable' => 'Storage writable',
    ],

    'values' => [
        'configured' => 'Configured',
        'missing' => 'Missing',
        'connected' => 'Connected',
        'unavailable' => 'Unavailable',
        'present' => 'Present',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'writable' => 'Writable',
        'not_writable' => 'Not writable',
        'sync' => 'sync',
        'database' => 'database',
        'custom' => ':value',
    ],

    'badges' => [
        'ok' => 'OK',
        'review' => 'Review',
    ],

    'messages' => [
        'ready' => 'Ready for restricted hosting',
        'needs_attention' => 'Setup needs attention',
        'status_only' => 'This page is protected and status-only. It does not run migrations or expose a public installer.',
        'database_error' => 'Database error',
    ],

    'pending' => [
        'heading' => 'Pending migrations',
        'description' => 'Run migrations through a protected web updater in a later step, or through deployment tooling where terminal access is available.',
    ],

    'actions' => [
        'refresh' => 'Refresh status',
    ],
];
