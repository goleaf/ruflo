<?php

return [
    'pages' => [
        'index' => [
            'title' => 'Activity history',
            'description' => 'Review meaningful private workspace changes without depending on background workers or external audit services.',
        ],
    ],

    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
        'open_tasks' => 'Open tasks',
        'open_subject' => 'Open task',
        'open_full_history' => 'Full history',
        'load_more' => 'Load more',
    ],

    'summary' => [
        'total' => 'Total',
        'today' => 'Today',
        'tasks' => 'Tasks',
        'checklist' => 'Checklist',
    ],

    'timeline' => [
        'label' => 'Private history',
        'heading' => 'Workspace activity',
        'description' => 'Only activity from your workspace appears here. Deleted tasks keep a safe title snapshot but do not expose a stale task link.',
        'loaded' => ':count loaded',
        'actor_time' => ':actor, :time',
    ],

    'task_timeline' => [
        'label' => 'Task history',
        'heading' => 'Timeline',
        'description' => 'Review meaningful changes for this task. Deleted related details use safe snapshots instead of stale object links.',
        'empty' => [
            'title' => 'No timeline entries yet',
            'description' => 'Create, update, complete, archive, restore, or edit checklist items to build this task history.',
        ],
    ],

    'actor' => [
        'system' => 'System',
    ],

    'subjects' => [
        'deleted' => 'Deleted task',
        'item' => 'Checklist item',
    ],

    'changes' => [
        'none' => 'No public field details',
        'summary' => 'Changed: :fields',
    ],

    'fields' => [
        'title' => 'title',
        'priority' => 'priority',
        'due_date' => 'due date',
        'project' => 'project',
        'category' => 'category',
        'goal' => 'goal',
        'milestone' => 'milestone',
        'habit' => 'habit',
        'tags' => 'tags',
        'details' => 'details',
    ],

    'empty' => [
        'title' => 'No activity yet',
        'description' => 'Task changes will appear here after you create, update, complete, archive, or restore work.',
    ],

    'events' => [
        'todo' => [
            'created' => [
                'label' => 'Created task',
                'description' => 'Created :subject.',
            ],
            'updated' => [
                'label' => 'Updated task',
                'description' => 'Updated :subject. :changes.',
            ],
            'completed' => [
                'label' => 'Completed task',
                'description' => 'Completed :subject.',
            ],
            'reopened' => [
                'label' => 'Reopened task',
                'description' => 'Reopened :subject.',
            ],
            'archived' => [
                'label' => 'Archived task',
                'description' => 'Archived :subject.',
            ],
            'unarchived' => [
                'label' => 'Unarchived task',
                'description' => 'Returned :subject from the archive.',
            ],
            'deleted' => [
                'label' => 'Moved to trash',
                'description' => 'Moved :subject to trash.',
            ],
            'restored' => [
                'label' => 'Restored task',
                'description' => 'Restored :subject from trash.',
            ],
            'checklist_created' => [
                'label' => 'Added checklist item',
                'description' => 'Added :item to :subject.',
            ],
            'checklist_updated' => [
                'label' => 'Updated checklist item',
                'description' => 'Updated :item on :subject.',
            ],
            'checklist_completed' => [
                'label' => 'Completed checklist item',
                'description' => 'Completed :item on :subject.',
            ],
            'checklist_reopened' => [
                'label' => 'Reopened checklist item',
                'description' => 'Reopened :item on :subject.',
            ],
            'checklist_moved' => [
                'label' => 'Moved checklist item',
                'description' => 'Reordered :item on :subject.',
            ],
            'checklist_deleted' => [
                'label' => 'Deleted checklist item',
                'description' => 'Deleted :item from :subject.',
            ],
        ],

        'todos' => [
            'completed_cleared' => [
                'label' => 'Cleared completed tasks',
                'description' => 'Moved :count completed tasks to trash.',
            ],
        ],
    ],
];
