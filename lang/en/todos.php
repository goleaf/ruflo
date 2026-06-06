<?php

return [
    'navigation' => [
        'label' => 'Todos',
    ],

    'pages' => [
        'index' => [
            'title' => 'Todos',
            'description' => 'Capture the next useful action, organize it, and move tasks cleanly through their lifecycle.',
        ],
        'show' => [
            'title' => 'Task details',
            'description' => 'Review one private task without exposing another workspace.',
        ],
    ],

    'fields' => [
        'title' => 'Task',
        'title_placeholder' => 'What needs doing?',
        'status' => 'Status',
        'priority' => 'Priority',
        'due_date' => 'Due date',
        'no_due_date' => 'No due date',
        'project' => 'Project',
        'no_project' => 'No project',
        'project_name' => 'New project name',
        'tags' => 'Tags',
        'no_tags' => 'No tags',
        'tag_name' => 'New tag name',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
    ],

    'summary' => [
        'active' => 'Active',
        'overdue' => 'Overdue',
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

    'priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'filters' => [
        'search' => 'Search',
        'search_placeholder' => 'Search tasks…',
        'project' => 'Project',
        'all_projects' => 'All projects',
        'tag' => 'Tag',
        'all_tags' => 'All tags',
        'priority' => 'Priority',
        'all_priorities' => 'All priorities',
        'due' => 'Due',
        'all_dates' => 'Any date',
        'due_today' => 'Due today',
        'overdue' => 'Overdue',
        'upcoming' => 'Upcoming',
        'with_due_date' => 'With due date',
        'without_due_date' => 'Without due date',
        'sort' => 'Sort by',
        'direction' => 'Order',
    ],

    'sort' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'due' => 'Due date',
        'priority' => 'Priority',
        'project' => 'Project',
        'title' => 'Title',
        'asc' => 'Ascending',
        'desc' => 'Descending',
    ],

    'actions' => [
        'add' => 'Add',
        'complete' => 'Complete task',
        'reopen' => 'Reopen task',
        'edit' => 'Edit task',
        'save' => 'Save changes',
        'cancel' => 'Cancel',
        'archive' => 'Archive',
        'restore' => 'Restore',
        'delete' => 'Delete',
        'more' => 'More actions',
        'back_to_list' => 'Back to tasks',
        'manage' => 'Manage',
        'rename' => 'Rename',
        'clear_completed' => 'Clear completed',
        'clear_filters' => 'Reset',
    ],

    'bulk' => [
        'selected' => ':count selected',
        'selected_items' => 'selected tasks',
        'selected_item' => 'selected task',
        'select_one' => 'Select task',
        'complete' => 'Complete',
        'archive' => 'Archive',
        'restore' => 'Restore',
        'move_to' => 'Move to',
        'move' => 'Move',
        'delete' => 'Delete',
    ],

    'confirmations' => [
        'delete' => 'Delete this task? You can no longer act on it from your lists.',
        'bulk_delete' => 'Delete the selected tasks?',
        'clear_completed' => 'Delete all completed tasks?',
        'delete_project' => 'Delete this project? Its tasks are kept and moved to "No project".',
        'delete_tag' => 'Delete this tag? It is removed from any tasks using it.',
    ],

    'empty' => [
        'active' => [
            'title' => 'No active tasks.',
            'description' => 'Add one focused item — or adjust your filters to see more.',
        ],
        'completed' => [
            'title' => 'Nothing completed yet.',
            'description' => 'Tasks you finish will appear here.',
        ],
        'archived' => [
            'title' => 'Your archive is empty.',
            'description' => 'Archive a task to set it aside without deleting it.',
        ],
        'search' => [
            'title' => 'No matching tasks.',
        ],
        'filtered' => [
            'description' => 'Clear search or reset filters to widen the list.',
        ],
        'due' => [
            'today' => [
                'title' => 'No tasks due today.',
            ],
            'overdue' => [
                'title' => 'No overdue tasks.',
            ],
            'upcoming' => [
                'title' => 'No upcoming tasks.',
            ],
            'with' => [
                'title' => 'No tasks with a due date.',
            ],
            'without' => [
                'title' => 'No tasks without a due date.',
            ],
        ],
        'priority' => [
            'title' => 'No :priority priority tasks.',
        ],
        'project' => [
            'title' => 'No tasks in this project.',
        ],
        'project_none' => [
            'title' => 'No tasks without a project.',
        ],
        'tag' => [
            'title' => 'No tasks with this tag.',
        ],
        'projects' => [
            'title' => 'No projects yet.',
        ],
        'tags' => [
            'title' => 'No tags yet.',
        ],
    ],

    'messages' => [
        'created' => 'Task added.',
        'updated' => 'Task updated.',
        'completed' => 'Task completed.',
        'reopened' => 'Task reopened.',
        'archived' => 'Task archived.',
        'restored' => 'Task restored.',
        'deleted' => 'Task deleted.',
        'completed_cleared' => 'Completed tasks cleared.',
        'bulk_done' => 'Updated :count task(s).',
        'cannot_change_completion_archived' => 'Restore this task before completing or reopening it.',
        'cannot_edit_archived' => 'Restore this task before editing it.',
        'project_created' => 'Project created.',
        'project_updated' => 'Project updated.',
        'project_deleted' => 'Project deleted.',
        'tag_created' => 'Tag created.',
        'tag_deleted' => 'Tag deleted.',
    ],

    'validation' => [
        'owned_active_project' => 'Choose one of your active projects.',
        'owned_tag' => 'Choose one of your tags.',
        'owned_todo' => 'Choose one of your tasks.',
    ],

    'modals' => [
        'edit' => [
            'heading' => 'Edit task',
            'description' => 'Update the task details. This does not change its status.',
        ],
        'manage' => [
            'heading' => 'Projects & tags',
            'description' => 'Organize your private workspace. Archiving or deleting these never deletes your tasks.',
            'projects' => 'Projects',
            'tags' => 'Tags',
        ],
    ],
];
