<?php

return [
    'navigation' => 'Maintenance',

    'pages' => [
        'center' => [
            'title' => 'Maintenance center',
            'heading' => 'Maintenance center',
            'subheading' => 'Run protected, web-safe maintenance checks and cleanup actions.',
        ],
    ],

    'sections' => [
        'health' => 'Health checks',
        'processing' => 'Web processing profile',
        'runtime' => 'Runtime state',
        'safe_controls' => 'Safe cleanup controls',
        'planned_tools' => 'Planned processing tools',
    ],

    'fields' => [
        'engine' => 'Engine',
        'chunk_size' => 'Chunk size',
        'max_runtime' => 'Max runtime',
        'retry_cooldown' => 'Retry cooldown',
        'resume' => 'Resume after failure',
        'detail_limit' => 'Detail rows',
        'cache_store' => 'Cache store',
        'session_driver' => 'Session driver',
        'compiled_views' => 'Compiled views',
    ],

    'values' => [
        'manual_livewire_chunks' => 'Manual Livewire chunks',
        'seconds' => ':count seconds',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ],

    'badges' => [
        'ok' => 'OK',
        'review' => 'Review',
    ],

    'messages' => [
        'ready' => 'Maintenance checks are healthy',
        'review_needed' => 'Maintenance checks need review',
        'web_only' => 'This center uses authenticated Livewire actions only. It does not require cron, queue workers, shell access, or Artisan access.',
        'safe_controls' => 'These actions are bounded to cache and compiled view cleanup. They do not run migrations or delete user data.',
        'planned_tools' => 'Retry/resume processors, demo seed generation, import/export cleanup, and storage cleanup attach here in later planned steps.',
        'cache_flushed' => 'Application cache flushed.',
        'compiled_views_cleared' => '{0} No compiled views were cleared.|{1} Cleared :count compiled view.|[2,*] Cleared :count compiled views.',
    ],

    'actions' => [
        'refresh' => 'Refresh',
        'clear_views' => 'Clear compiled views',
        'flush_cache' => 'Flush application cache',
    ],

    'confirmations' => [
        'clear_views' => 'Clear compiled Blade views?',
        'flush_cache' => 'Flush the application cache?',
    ],
];
