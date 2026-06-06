<?php

return [
    'pages' => [
        'inbox' => [
            'title' => 'Notifications',
            'description' => 'Review private in-app notifications and control read state.',
        ],
    ],

    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
        'mark_all_read' => 'Mark all read',
        'open' => 'Open',
        'mark_read' => 'Mark read',
        'mark_unread' => 'Mark unread',
    ],

    'summary' => [
        'all' => 'All',
        'unread' => 'Unread',
        'read' => 'Read',
    ],

    'list' => [
        'heading' => 'Notification center',
        'description' => 'Only notifications for your private workspace appear here.',
    ],

    'filters' => [
        'label' => 'Notification filters',
        'all' => 'All',
        'unread' => 'Unread',
        'read' => 'Read',
    ],

    'status' => [
        'unread' => 'Unread',
        'read' => 'Read',
    ],

    'messages' => [
        'marked_read' => 'Notification marked read.',
        'marked_unread' => 'Notification marked unread.',
        'all_marked_read' => 'All notifications marked read.',
    ],

    'empty' => [
        'title' => 'No notifications yet',
        'description' => 'Due reminders and future in-app events will appear here.',
    ],

    'comments' => [
        'created' => [
            'title' => 'New task comment',
            'message' => ':author commented on ":task".',
        ],
        'mentioned' => [
            'title' => 'You were mentioned',
            'message' => ':author mentioned you on ":task".',
        ],
    ],

    'fallback' => [
        'title' => 'Notification',
        'message' => 'No notification details were provided.',
        'kind' => 'Notification',
    ],
];
