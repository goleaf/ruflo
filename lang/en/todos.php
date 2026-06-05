<?php

return [
    'navigation' => [
        'label' => 'Todos',
    ],

    'pages' => [
        'index' => [
            'title' => 'Todos',
            'description' => 'Capture the next useful action, keep private work scoped, and move tasks cleanly through their lifecycle.',
        ],
    ],

    'fields' => [
        'title' => 'Task',
        'title_placeholder' => 'What needs doing?',
    ],

    'summary' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'tabs' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'status' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'actions' => [
        'add' => 'Add',
        'toggle' => 'Toggle completion',
        'edit' => 'Edit task',
        'save' => 'Save changes',
        'cancel' => 'Cancel',
        'archive' => 'Archive',
        'restore' => 'Restore',
        'delete' => 'Delete',
        'clear_completed' => 'Clear completed',
    ],

    'confirmations' => [
        'delete' => 'Delete this task? You can no longer act on it from your lists.',
    ],

    'empty' => [
        'active' => [
            'title' => 'No active tasks.',
            'description' => 'Add one focused item to start building momentum.',
        ],
        'completed' => [
            'title' => 'Nothing completed yet.',
            'description' => 'Tasks you finish will appear here.',
        ],
        'archived' => [
            'title' => 'Your archive is empty.',
            'description' => 'Archive a task to set it aside without deleting it.',
        ],
    ],

    'messages' => [
        'created' => 'Task added.',
        'updated' => 'Task updated.',
        'archived' => 'Task archived.',
        'restored' => 'Task restored.',
        'deleted' => 'Task deleted.',
        'completed_cleared' => 'Completed tasks cleared.',
        'cannot_toggle_archived' => 'Restore this task before completing or reopening it.',
        'cannot_edit_archived' => 'Restore this task before editing it.',
    ],

    'modals' => [
        'edit' => [
            'heading' => 'Edit task',
            'description' => 'Update the task title. This does not change its status.',
        ],
    ],
];
