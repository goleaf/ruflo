<?php

return [
    'pages' => [
        'index' => [
            'title' => 'Automations',
            'description' => 'Run safe task rules from the browser with bounded chunks, visible progress, retry, and resume.',
        ],
    ],

    'summary' => [
        'rules' => 'Rules',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ],

    'fields' => [
        'name' => 'Rule name',
        'name_placeholder' => 'Name this automation',
        'kind' => 'Rule type',
    ],

    'create' => [
        'label' => 'Web-only rules',
        'heading' => 'Create an automation',
        'description' => 'Rules run only when you test or run them from this page. No cron, queue worker, terminal, or paid service is required.',
    ],

    'rules' => [
        'label' => 'Manual processing',
        'heading' => 'Automation rules',
        'description' => 'Each run processes a bounded owner-scoped chunk and stores the latest report so you can retry or run again to resume remaining work.',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'last_run' => 'Last run',
        'never_run' => 'Never run',
        'runs' => 'Runs',
        'latest_matched' => 'Matched',
        'latest_changed' => 'Changed',
        'latest_summary' => 'Latest run matched :matched, changed :changed, and left :remaining for a later run.',
    ],

    'kinds' => [
        'promote_overdue_tasks' => [
            'label' => 'Promote overdue tasks',
            'description' => 'Finds active overdue low or normal priority tasks and raises them to high priority.',
        ],
        'archive_completed_tasks' => [
            'label' => 'Archive completed tasks',
            'description' => 'Finds completed tasks older than seven days and moves them to the archive.',
        ],
    ],

    'run_status' => [
        'completed' => 'Completed',
        'disabled' => 'Disabled',
        'failed' => 'Failed',
    ],

    'run_report' => [
        'label' => 'Latest report',
        'heading' => ':rule finished',
        'matched' => 'Matched',
        'changed' => 'Changed',
        'remaining' => 'Remaining',
        'mode' => 'Mode',
        'dry_run' => 'Test',
        'live_run' => 'Run',
    ],

    'actions' => [
        'open_automations' => 'Automations',
        'create' => 'Create rule',
        'enable' => 'Enable',
        'disable' => 'Disable',
        'test' => 'Test',
        'run' => 'Run now',
    ],

    'messages' => [
        'created' => ':name was created.',
        'toggled' => 'Automation rule updated.',
        'tested' => 'Automation test finished.',
        'ran' => 'Automation run finished.',
    ],

    'runs' => [
        'messages' => [
            'completed' => 'The rule completed its current browser-triggered chunk.',
            'disabled' => 'The rule is disabled, so no tasks were changed.',
            'failed' => 'The rule could not finish. No private data was exposed.',
        ],
    ],

    'validation' => [
        'rule_name' => 'Enter a rule name between 1 and 80 characters.',
        'rule_name_unique' => 'You already have an automation rule with that name.',
    ],

    'empty' => [
        'title' => 'No automation rules yet',
        'description' => 'Create a rule, test it, then run it when you want the browser-triggered chunk to make changes.',
    ],

    'unavailable' => 'Unavailable',
];
