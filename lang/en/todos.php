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
    ],

    'fields' => [
        'title' => 'Task',
        'title_placeholder' => 'What needs doing?',
        'priority' => 'Priority',
        'due_date' => 'Due date',
        'project' => 'Project',
        'no_project' => 'No project',
        'project_name' => 'New project name',
        'tags' => 'Tags',
        'tag_name' => 'New tag name',
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
        'sort' => 'Sort by',
        'direction' => 'Order',
    ],

    'sort' => [
        'created' => 'Created',
        'due' => 'Due date',
        'priority' => 'Priority',
        'title' => 'Title',
        'asc' => 'Ascending',
        'desc' => 'Descending',
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
        'more' => 'More actions',
        'manage' => 'Manage',
        'clear_completed' => 'Clear completed',
        'clear_filters' => 'Reset',
    ],

    'bulk' => [
        'selected' => ':count selected',
        'select_one' => 'Select task',
        'complete' => 'Complete',
        'archive' => 'Archive',
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
        'archived' => 'Task archived.',
        'restored' => 'Task restored.',
        'deleted' => 'Task deleted.',
        'completed_cleared' => 'Completed tasks cleared.',
        'bulk_done' => 'Updated :count task(s).',
        'cannot_toggle_archived' => 'Restore this task before completing or reopening it.',
        'cannot_edit_archived' => 'Restore this task before editing it.',
        'project_created' => 'Project created.',
        'project_deleted' => 'Project deleted.',
        'tag_created' => 'Tag created.',
        'tag_deleted' => 'Tag deleted.',
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
