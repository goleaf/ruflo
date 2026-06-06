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
        'templates' => [
            'title' => 'Task templates',
            'description' => 'Turn repeatable tasks, projects, checklists, and routines into one-click private workspace items.',
        ],
        'inbox' => [
            'title' => 'Inbox',
            'description' => 'Capture loose tasks quickly and triage them into projects, dates, priorities, and tags later.',
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
        'delete_checklist_item' => 'Delete this checklist item?',
        'delete_template' => 'Delete this template? Existing tasks created from it are kept.',
    ],

    'checklist' => [
        'label' => 'Subtasks',
        'heading' => 'Checklist',
        'description' => 'Break this task into ordered, contained checklist items.',
        'progress_label' => 'Checklist progress',
        'progress' => ':completed of :total complete',
        'completed_at' => 'Completed :date',
        'fields' => [
            'item_title' => 'Checklist item',
            'item_placeholder' => 'Add a subtask or checklist item',
        ],
        'actions' => [
            'add' => 'Add item',
            'save' => 'Save item',
            'cancel' => 'Cancel',
            'edit' => 'Edit item',
            'delete' => 'Delete item',
            'move_up' => 'Move up',
            'move_down' => 'Move down',
            'mark_complete' => 'Mark item complete',
            'mark_incomplete' => 'Mark item incomplete',
        ],
        'empty' => [
            'title' => 'No checklist items.',
            'description' => 'Add the first contained subtask for this task.',
        ],
        'locked' => [
            'heading' => 'Checklist locked',
            'description' => 'Archived tasks keep their checklist for review. Unarchive the task before changing checklist items.',
        ],
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
        'checklist_item_created' => 'Checklist item added.',
        'checklist_item_updated' => 'Checklist item updated.',
        'checklist_item_completed' => 'Checklist item completed.',
        'checklist_item_reopened' => 'Checklist item reopened.',
        'checklist_item_deleted' => 'Checklist item deleted.',
        'checklist_item_moved' => 'Checklist item moved.',
        'cannot_change_checklist_archived' => 'Unarchive this task before changing its checklist.',
        'template_created' => 'Template ":name" saved.',
        'template_updated' => 'Template updated.',
        'template_deleted' => 'Template deleted.',
        'template_instantiated' => 'Created ":title" from template.',
        'inbox_captured' => 'Captured ":title".',
        'inbox_triaged' => 'Task removed from inbox.',
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
        'checklist_item_title' => 'Enter a checklist item with visible text.',
        'checklist_item_direction' => 'Choose a valid checklist movement.',
        'template_name' => 'Enter text with visible characters.',
        'template_name_unique' => 'Use a template name you have not already used.',
        'template_kind' => 'Choose a valid template type.',
        'template_visibility' => 'Choose a valid template visibility.',
        'template_due_offset' => 'Enter a due offset from 0 to 365 days.',
        'template_project_name_required' => 'Enter the project this template should create or reuse.',
        'template_checklist_items' => 'Use up to 10 checklist items, each with visible text up to 120 characters.',
        'template_checklist_items_required' => 'Checklist and routine templates need at least one checklist item.',
        'inbox_capture_title' => 'Enter a captured task with visible text up to 120 characters.',
        'inbox_todo' => 'Choose a captured inbox task.',
        'priority' => 'Choose a valid priority.',
        'due_date' => 'Enter a valid due date.',
    ],

    'inbox' => [
        'label' => 'Quick capture',
        'heading' => 'Captured tasks',
        'count' => 'Inbox tasks',
        'badge' => 'Needs triage',
        'captured_at' => 'Captured :time',
        'fields' => [
            'capture_title' => 'Capture task',
        ],
        'placeholders' => [
            'capture_title' => 'Type the task before it gets lost',
        ],
        'actions' => [
            'capture' => 'Capture',
            'triage' => 'Triage',
            'save_triage' => 'Save and remove from inbox',
            'open_inbox' => 'Inbox',
        ],
        'triage' => [
            'heading' => 'Triage captured task',
            'description' => 'Organize this task and remove it from the inbox.',
        ],
        'empty' => [
            'title' => 'Inbox is clear.',
            'description' => 'Quick captures will appear here until you triage them.',
        ],
    ],

    'templates' => [
        'create' => [
            'heading' => 'Create template',
            'description' => 'Save a reusable setup that creates real owner-scoped tasks, projects, and checklist rows.',
        ],
        'edit' => [
            'heading' => 'Edit template',
            'description' => 'Change the reusable defaults without changing tasks already created from this template.',
        ],
        'fields' => [
            'name' => 'Template name',
            'kind' => 'Template type',
            'visibility' => 'Visibility',
            'description' => 'Description',
            'project_name' => 'Project name',
            'due_offset_days' => 'Due in days',
            'checklist_items' => 'Checklist items',
        ],
        'placeholders' => [
            'name' => 'Weekly planning',
            'description' => 'What this template is best for',
            'title' => 'Task title created from this template',
            'project_name' => 'Project to create or reuse',
            'due_offset_days' => '0 for today',
        ],
        'kinds' => [
            'task' => 'Task',
            'project' => 'Project',
            'checklist' => 'Checklist',
            'routine' => 'Routine',
        ],
        'kind_descriptions' => [
            'task' => 'Create one task with saved defaults.',
            'project' => 'Create or reuse a project and add the task to it.',
            'checklist' => 'Create one task with contained checklist items.',
            'routine' => 'Create a repeatable manual routine without a scheduler.',
        ],
        'visibility' => [
            'private' => 'Private',
            'shared' => 'Shared',
        ],
        'preview' => [
            'due_offset' => 'Due in :days day(s)',
            'no_checklist' => 'No checklist items.',
        ],
        'actions' => [
            'create' => 'Save template',
            'use' => 'Create task',
            'open_templates' => 'Templates',
            'open_tasks' => 'Task list',
            'remove_checklist_item' => 'Remove checklist item',
        ],
        'empty' => [
            'title' => 'No templates yet.',
            'description' => 'Create a template to reuse task, project, checklist, and routine defaults.',
        ],
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
