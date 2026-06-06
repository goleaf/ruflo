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
        'today' => [
            'title' => 'Today',
            'description' => 'Focus on active tasks due today in your private workspace.',
        ],
        'overdue' => [
            'title' => 'Overdue',
            'description' => 'Review active tasks that are past their due date without exposing another workspace.',
        ],
        'upcoming' => [
            'title' => 'Upcoming',
            'description' => 'Plan active tasks with future due dates in your private workspace.',
        ],
        'calendar' => [
            'title' => 'Calendar',
            'description' => 'Review active due dates by month while reminders and recurring work remain web-only and self-hosted.',
        ],
        'board' => [
            'title' => 'Task board',
            'description' => 'Move private tasks between lifecycle columns and active projects.',
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
        'trash' => 'Trash',
    ],

    'tabs' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'trash' => 'Trash',
    ],

    'status' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'trash' => 'Trash',
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
        'active' => 'Active filters',
        'search_chip' => 'Search: :term',
        'project_chip' => 'Project: :project',
        'tag_chip' => 'Tag: :tag',
        'priority_chip' => 'Priority: :priority',
        'due_chip' => 'Due: :due',
        'sort_chip' => 'Sort: :sort',
        'direction_chip' => 'Order: :direction',
        'unavailable_filter' => 'Unavailable',
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
        'unarchive' => 'Unarchive',
        'restore' => 'Restore',
        'restore_from_trash' => 'Restore from trash',
        'delete' => 'Delete',
        'more' => 'More actions',
        'back_to_list' => 'Back to tasks',
        'manage' => 'Manage',
        'rename' => 'Rename',
        'clear_completed' => 'Clear completed',
        'clear_filters' => 'Reset',
    ],

    'saved_views' => [
        'label' => 'Saved views',
        'name' => 'View name',
        'name_placeholder' => 'Name this view',
        'save' => 'Save view',
        'delete' => 'Delete :name',
    ],

    'bulk' => [
        'selected' => ':count selected',
        'selected_items' => 'selected tasks',
        'selected_item' => 'selected task',
        'select_one' => 'Select task',
        'complete' => 'Complete',
        'archive' => 'Archive',
        'unarchive' => 'Unarchive',
        'restore' => 'Restore',
        'move_to' => 'Move to',
        'move' => 'Move',
        'delete' => 'Delete',
        'confirm_delete' => 'Move to Trash',
        'select_visible' => 'Select visible',
        'clear_selection' => 'Clear selection',
        'result' => 'Updated :affected of :selected selected task(s). Skipped :skipped. Failed :failed.',
    ],

    'confirmations' => [
        'delete' => 'Move this task to Trash? You can restore it later.',
        'bulk_delete' => 'Move the selected tasks to Trash?',
        'clear_completed' => 'Delete all completed tasks?',
        'delete_project' => 'Delete this project? Its tasks are kept and moved to "No project".',
        'delete_tag' => 'Delete this tag? It is removed from any tasks using it.',
        'delete_saved_view' => 'Delete this saved view?',
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
        'trash' => [
            'title' => 'Trash is empty.',
            'description' => 'Deleted tasks appear here until you restore them.',
        ],
        'search' => [
            'title' => 'No matching tasks.',
        ],
        'filtered' => [
            'title' => 'No matching tasks.',
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
        'project_detail' => [
            'title' => 'No tasks in this project.',
            'description' => 'Tasks assigned to this project will appear here.',
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
        'unarchived' => 'Task unarchived.',
        'deleted' => 'Task deleted.',
        'restored_from_trash' => 'Task restored from trash.',
        'completed_cleared' => 'Completed tasks cleared.',
        'bulk_done' => 'Updated :affected of :selected selected task(s). Skipped :skipped. Failed :failed.',
        'cannot_change_completion_archived' => 'Unarchive this task before completing or reopening it.',
        'cannot_edit_archived' => 'Unarchive this task before editing it.',
        'project_created' => 'Project created.',
        'project_updated' => 'Project updated.',
        'project_deleted' => 'Project deleted.',
        'tag_created' => 'Tag created.',
        'tag_deleted' => 'Tag deleted.',
        'saved_view_created' => 'Saved view ":name".',
        'saved_view_applied' => 'Applied saved view ":name".',
        'saved_view_deleted' => 'Saved view deleted.',
        'board_status_moved' => 'Task moved on the board.',
        'board_project_moved' => 'Task project updated from the board.',
    ],

    'today' => [
        'label' => 'Due today',
        'date' => 'Today, :date',
        'count' => 'Due today',
        'open_filtered' => 'Filtered list',
        'empty_description' => 'Tasks with today as their due date will appear here while they are active.',
    ],

    'overdue' => [
        'label' => 'Past due',
        'date' => 'Before :date',
        'count' => 'Overdue',
        'open_filtered' => 'Filtered list',
        'empty_description' => 'Active tasks appear here after their due date passes.',
    ],

    'upcoming' => [
        'label' => 'Future due dates',
        'date' => 'After :date',
        'count' => 'Upcoming',
        'open_filtered' => 'Filtered list',
        'empty_description' => 'Active tasks with future due dates will appear here.',
    ],

    'exceptions' => [
        'cannot_complete_archived' => 'Archived tasks must be unarchived before they can be completed.',
        'cannot_reopen_archived' => 'Archived tasks must be unarchived before they can be reopened.',
        'cannot_edit_archived' => 'Archived tasks must be unarchived before they can be edited.',
        'cannot_complete_trashed' => 'Deleted tasks must be restored from Trash before they can be completed.',
        'cannot_reopen_trashed' => 'Deleted tasks must be restored from Trash before they can be reopened.',
        'cannot_archive_trashed' => 'Deleted tasks must be restored from Trash before they can be archived.',
        'cannot_unarchive_trashed' => 'Deleted tasks must be restored from Trash before they can be unarchived.',
        'cannot_edit_trashed' => 'Deleted tasks must be restored from Trash before they can be edited.',
        'invalid_transition' => 'Cannot :transition a task while it is :status.',
    ],

    'validation' => [
        'owned_active_project' => 'Choose one of your active projects.',
        'owned_tag' => 'Choose one of your tags.',
        'owned_todo' => 'Choose one of your tasks.',
        'owned_deleted_todo' => 'Choose one of your deleted tasks.',
        'tag_name' => 'Enter a tag name with at least one letter or number.',
        'saved_view_name' => 'Enter a saved view name with visible text.',
        'saved_view_name_unique' => 'Use a saved view name you have not already used.',
        'board_status' => 'Choose a valid board column.',
        'calendar_month' => 'Enter a valid calendar month.',
        'priority' => 'Choose a valid priority.',
        'due_date' => 'Enter a valid due date.',
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
        'bulk_delete' => [
            'heading' => 'Move selected tasks to Trash?',
            'description' => 'You are moving :count selected task(s) to Trash. You can restore them later.',
        ],
    ],

    'projects' => [
        'show' => [
            'title' => 'Project details',
            'description' => 'Review this private project and the tasks assigned to it.',
            'tasks_heading' => 'Project tasks',
            'task_count' => ':count task(s)',
        ],
        'actions' => [
            'filter_tasks' => 'Filter tasks',
        ],
    ],

    'board' => [
        'open_board' => 'Board',
        'open_list' => 'List view',
        'column' => 'Column',
        'column_limit' => 'Showing :shown of :total tasks.',
        'target_status' => 'Board column',
        'project_label' => 'Project',
        'move_project' => 'Move project',
        'move_to' => [
            'active' => 'Move to active',
            'completed' => 'Move to completed',
            'archived' => 'Move to archived',
        ],
        'empty' => [
            'active' => [
                'title' => 'No active cards.',
                'description' => 'Active tasks will appear here.',
            ],
            'completed' => [
                'title' => 'No completed cards.',
                'description' => 'Completed tasks will appear here.',
            ],
            'archived' => [
                'title' => 'No archived cards.',
                'description' => 'Archived tasks will appear here.',
            ],
        ],
    ],

    'calendar' => [
        'open_calendar' => 'Calendar',
        'open_list' => 'List view',
        'month' => 'Month',
        'change_month' => 'Change month',
        'previous_month' => 'Previous',
        'next_month' => 'Next',
        'current_month' => 'This month',
        'selected_month' => 'Selected month',
        'range' => ':start through :end',
        'today_badge' => 'Today',
        'empty_day' => 'No active due tasks.',
        'outside_month' => 'Outside month',
        'more_tasks' => '+:count more',
        'stats' => [
            'month' => 'This month',
            'today' => 'Today',
            'overdue' => 'Overdue',
            'upcoming' => 'Upcoming',
            'no_due_date' => 'No date',
        ],
        'invalid_month' => [
            'heading' => 'Month reset',
            'text' => 'The requested month was not valid, so the calendar returned to the current month.',
        ],
        'no_due_date' => [
            'heading' => 'Unscheduled tasks',
            'empty_title' => 'No unscheduled tasks.',
            'empty_description' => 'Active tasks without due dates will appear here.',
        ],
        'reminders' => [
            'heading' => 'Reminders',
            'description' => 'Reminder scheduling will appear here after the web-mode reminder step adds owned schedule data.',
        ],
        'recurrence' => [
            'heading' => 'Recurring tasks',
            'description' => 'Recurring task rules will appear here after the recurring-task steps add rules, occurrences, and exceptions.',
        ],
    ],
];
