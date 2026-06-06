<?php

return [
    'pages' => [
        'index' => [
            'title' => 'Reminders',
            'description' => 'Schedule private task reminders and process due reminders from the browser.',
        ],
    ],

    'navigation' => [
        'label' => 'Reminders',
    ],

    'summary' => [
        'pending' => 'Pending',
        'due' => 'Due now',
        'processed' => 'Processed',
        'skipped' => 'Skipped',
    ],

    'fields' => [
        'task' => 'Task',
        'choose_task' => 'Choose a task',
        'remind_at' => 'Reminder time',
    ],

    'status' => [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'skipped' => 'Skipped',
    ],

    'actions' => [
        'back_to_tasks' => 'Back to tasks',
        'open_reminders' => 'Open reminders',
        'process_now' => 'Process due',
        'schedule' => 'Schedule reminder',
        'clear' => 'Clear',
    ],

    'web_mode' => [
        'heading' => 'Web-triggered processing',
        'description' => 'Due reminders are checked when you open the dashboard, open this page, or press Process due. There is no cron, queue worker, or email dependency.',
    ],

    'preferences' => [
        'heading' => 'Reminder preference',
        'description' => 'Pause reminder processing for this private workspace without deleting scheduled reminders.',
        'enabled' => 'Enabled',
        'disabled' => 'Paused',
        'toggle' => 'Process due reminders for this account',
    ],

    'local' => [
        'heading' => 'Local browser notifications',
        'description' => 'Show native browser alerts for pending reminders while RuFlo is open in this browser.',
        'permission' => 'Browser permission',
        'loaded' => 'Loaded reminders',
        'enabled' => 'Local alerts on',
        'disabled' => 'Local alerts off',
        'enable' => 'Enable browser alerts',
        'disable' => 'Disable',
        'test' => 'Test alert',
        'pending_count' => ':count pending',
        'permission_default' => 'Not requested',
        'permission_granted' => 'Allowed',
        'permission_denied' => 'Blocked',
        'unsupported' => 'This browser cannot show local notifications.',
        'secure_required' => 'Open RuFlo over HTTPS to use browser notifications.',
        'denied_help' => 'Browser notifications are blocked for this site.',
        'ready' => 'This browser will alert when a loaded reminder becomes due.',
        'offline' => 'Enable alerts to let this browser watch loaded reminders.',
        'failed' => 'The browser refused to show the notification.',
        'storage_unavailable' => 'This browser could not save the local notification setting.',
        'test_title' => 'RuFlo reminder test',
        'test_body' => 'Browser notifications are ready for this workspace.',
    ],

    'create' => [
        'heading' => 'Schedule a reminder',
        'description' => 'Choose one active task and the browser-local date and time to check it.',
    ],

    'list' => [
        'heading' => 'Recent reminders',
        'description' => 'Pending, processed, and skipped reminders stay visible so manual runs can be retried or reviewed.',
    ],

    'empty' => [
        'title' => 'No reminders yet.',
        'description' => 'Schedule the first reminder for an active task.',
    ],

    'messages' => [
        'scheduled' => 'Reminder scheduled for :task.',
        'cleared' => 'Reminder cleared.',
        'processed' => 'Due reminders processed.',
        'enabled' => 'Reminder processing enabled.',
        'disabled' => 'Reminder processing paused.',
    ],

    'validation' => [
        'todo_required' => 'Choose an active task for the reminder.',
        'todo_actionable' => 'Reminders can only be scheduled for your active tasks.',
        'remind_at' => 'Enter a valid reminder time.',
        'remind_at_future' => 'Choose a reminder time that is not in the past.',
    ],

    'processing' => [
        'processed' => 'Database notification created.',
        'unknown_task' => 'Unavailable task',
        'report_heading' => 'Reminder processing report',
        'report' => 'Matched :matched, processed :processed, skipped :skipped, failed :failed, remaining :remaining.',
        'skipped_reason' => 'Skipped: :reason',
        'skipped' => [
            'preferences_disabled' => 'Reminder processing is paused.',
            'task_not_actionable' => 'The task is completed, archived, deleted, or unavailable.',
            'processing_failed' => 'Processing failed for this reminder.',
            'generic' => 'The reminder was skipped.',
        ],
    ],

    'notifications' => [
        'todo_due' => [
            'title' => 'Task reminder due',
            'message' => 'Reminder due for ":task".',
        ],
        'daily_summary' => [
            'title' => 'Daily summary',
            'message' => ':due due today, :overdue overdue.',
        ],
    ],
];
