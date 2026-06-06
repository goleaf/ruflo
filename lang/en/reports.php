<?php

return [
    'navigation' => [
        'label' => 'Reports',
    ],

    'pages' => [
        'overview' => [
            'title' => 'Reports',
            'description' => 'Owner-scoped productivity, habit, project, time, and overdue reports calculated when you open the page.',
        ],
    ],

    'overview' => [
        'label' => 'Reports overview',
        'heading' => 'Local workspace reports',
        'description' => 'Private counters and browser-rendered trend bars summarize the current account without background jobs or external reporting services.',
        'generated' => 'Generated :date',
    ],

    'widgets' => [
        'label' => 'Reports summary widgets',
        'productivity' => [
            'label' => 'Productivity',
            'description' => 'Completed tasks this week with active, due-today, and next-seven-day context.',
        ],
        'habits' => [
            'label' => 'Habits',
            'description' => 'Active routines and check-ins from the private habit tracker.',
        ],
        'projects' => [
            'label' => 'Projects',
            'description' => 'Active lists, project task coverage, overdue project work, and unassigned tasks.',
        ],
        'time' => [
            'label' => 'Time',
            'description' => 'Completed time logged this week compared with today and the previous week.',
        ],
        'overdue' => [
            'label' => 'Overdue trend',
            'description' => 'Current overdue work grouped by age and priority so stale deadlines remain visible.',
        ],
    ],

    'badges' => [
        'active' => 'Active',
        'adherence' => 'Adherence',
        'needs_review' => 'Needs review',
        'this_week' => 'This week',
    ],

    'actions' => [
        'open_habits' => 'Open habits',
        'open_overdue' => 'Review overdue',
        'open_projects' => 'Review projects',
        'open_tasks' => 'Open tasks',
        'open_time' => 'Track time',
    ],

    'settings' => [
        'compact' => 'Compact',
        'details' => 'Details',
        'hide_trends' => 'Hide trends',
        'show_trends' => 'Show trends',
    ],

    'metrics' => [
        'active' => 'Active',
        'active_timers' => 'Active timers',
        'checked_today' => 'Checked today',
        'completed' => 'Completed',
        'completed_this_week' => 'Completed this week',
        'completion_percent' => 'Completion',
        'delta' => 'Change',
        'due_next_7_days' => 'Next 7 days',
        'due_today' => 'Due today',
        'eight_plus_days' => '8+ days',
        'high' => 'High',
        'no_project' => 'No project',
        'oldest_age_days' => 'Oldest age',
        'overdue' => 'Overdue',
        'previous_week' => 'Previous week',
        'this_week' => 'This week',
        'today' => 'Today',
        'urgent' => 'Urgent',
        'with_active_tasks' => 'With active tasks',
    ],

    'values' => [
        'days' => ':daysd',
        'hours_minutes' => ':hoursh :minutesm',
        'minutes' => ':minutesm',
        'percent' => ':percent%',
        'zero_minutes' => '0m',
    ],

    'empty' => [
        'heading' => 'No report activity yet',
        'description' => 'Create tasks, complete work, check in habits, assign projects, or log time to populate local reports.',
    ],

    'projects' => [
        'label' => 'Project reports',
        'heading' => 'Project highlights',
        'description' => 'Active owner projects are ranked by overdue and active work so private list health is visible without exposing another workspace.',
        'cards_label' => 'Project report cards',
        'progress_aria' => ':project is :percent% complete in the report overview.',
        'badges' => [
            'active' => ':count active projects',
            'overdue' => ':count overdue project tasks',
            'project_overdue' => ':count overdue',
        ],
        'metrics' => [
            'active' => 'Active',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            'progress' => 'Progress',
        ],
        'actions' => [
            'filter_project' => 'Filter project tasks',
        ],
        'empty' => [
            'heading' => 'No project report yet',
            'description' => 'Create an active project or assign tasks to a project to see project report cards.',
        ],
    ],

    'charts' => [
        'label' => 'Report trend',
        'item_summary' => ':label report value is :value.',
        'minutes_summary' => ':label report value is :value minutes.',
        'aria' => [
            'productivity' => 'Productivity report chart with :active active tasks and :completed completed tasks this week.',
            'overdue' => 'Overdue report chart with :total overdue tasks and the oldest task :oldest days late.',
            'habits' => 'Habit report chart with :active active habits and :check_ins check-ins this week.',
            'time' => 'Time report chart with :week logged this week and :previous logged during the previous week.',
        ],
        'productivity' => [
            'label' => 'Productivity trend',
            'description' => 'Compares active, completed, due-today, and upcoming private task counts.',
            'active' => 'Active',
            'completed' => 'Completed',
            'due_today' => 'Due today',
            'due_next_7_days' => 'Next 7 days',
        ],
        'overdue' => [
            'label' => 'Overdue age',
            'description' => 'Groups overdue private tasks by how long they have been late.',
            'one_to_three_days' => '1-3 days',
            'four_to_seven_days' => '4-7 days',
            'eight_plus_days' => '8+ days',
        ],
        'habits' => [
            'label' => 'Habit momentum',
            'description' => 'Compares active habits, today check-ins, weekly check-ins, and distinct routines touched this week.',
            'active' => 'Active habits',
            'checked_today' => 'Checked today',
            'this_week' => 'This week',
            'distinct' => 'Distinct habits',
        ],
        'time' => [
            'label' => 'Time trend',
            'description' => 'Compares completed private time entries in browser-rendered minute bars.',
            'today' => 'Today',
            'this_week' => 'This week',
            'previous_week' => 'Previous week',
        ],
    ],

    'details' => [
        'label' => 'Report details',
        'heading' => 'Readable report notes',
        'description' => 'Compact narrative summaries mirror the chart data for screen-reader users and quick scanning.',
        'productivity' => 'Productivity',
        'productivity_summary' => ':delta task completion change versus the previous week, with :inbox inbox tasks still needing triage.',
        'habits' => 'Habits',
        'habits_summary' => ':delta habit check-in change versus the previous week, with :habits distinct active habits checked this week.',
        'projects' => 'Projects',
        'projects_summary' => ':projects active projects currently have active tasks, and :unassigned active tasks have no project.',
        'time' => 'Time',
        'time_summary' => ':delta tracked-time change versus the previous week, with :timers active timers.',
    ],

    'privacy' => [
        'heading' => 'Private local reports',
        'description' => 'Reports are calculated from the authenticated user boundary on normal web requests. Foreign, archived, deleted, and unauthorized records are excluded before rendering.',
    ],
];
