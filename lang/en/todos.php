<?php

return [
    'navigation' => [
        'label' => 'Todos',
    ],

    'pages' => [
        'index' => [
            'title' => 'Todos',
            'description' => 'Capture the next useful action, keep private work scoped, and leave room for richer planning features.',
        ],
    ],

    'fields' => [
        'title' => 'Task',
    ],

    'summary' => [
        'remaining' => 'Remaining',
        'completed' => 'Completed',
    ],

    'actions' => [
        'add' => 'Add',
        'toggle' => 'Toggle todo',
        'delete' => 'Delete todo',
        'clear_completed' => 'Clear completed',
    ],

    'empty' => [
        'title' => 'No todos yet.',
        'description' => 'Add one focused item to start building your private workspace.',
    ],

    'messages' => [
        'created' => 'Todo added.',
        'deleted' => 'Todo deleted.',
        'completed_cleared' => 'Completed todos cleared.',
    ],
];
