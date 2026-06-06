<?php

return [
    'title' => 'RuFlo private productivity workspace',
    'image_alt' => 'RuFlo product banner',
    'heading' => 'RuFlo',
    'description' => 'A private productivity workspace for turning tasks, projects, goals, habits, reminders, and focused work sessions into one owner-scoped operating system.',

    'brand' => [
        'aria' => 'RuFlo home',
        'name' => 'RuFlo',
        'line' => 'Private planning, execution, and review',
    ],

    'nav' => [
        'aria' => 'Landing page navigation',
        'features' => 'Features',
        'workflow' => 'Workflow',
        'privacy' => 'Privacy',
        'dashboard' => 'Open dashboard',
        'sign_in' => 'Sign in',
        'create' => 'Create workspace',
    ],

    'hero' => [
        'eyebrow' => 'Owner-scoped task control',
        'primary_cta' => 'Create your workspace',
        'secondary_cta' => 'Sign in',
        'dashboard_cta' => 'Open dashboard',
    ],

    'proof' => [
        'aria' => 'RuFlo feature summary',
        'items' => [
            [
                'value' => '11',
                'label' => 'dashboard counters across tasks, projects, tags, goals, milestones, habits, and check-ins',
            ],
            [
                'value' => '7',
                'label' => 'task focus views for today, overdue, upcoming, blocked, cleanup, automations, and time',
            ],
            [
                'value' => '0',
                'label' => 'cron or worker requirements for web-triggered reminder processing',
            ],
            [
                'value' => '100%',
                'label' => 'owner-scoped lists, filters, counters, and linked records',
            ],
        ],
    ],

    'features' => [
        'eyebrow' => 'All core features',
        'heading' => 'Everything needed to run a private workspace.',
        'description' => 'RuFlo keeps capture, planning, execution, review, and automation in the same authenticated boundary, so every view works from the same private data model.',
    ],

    'feature_groups' => [
        [
            'label' => 'Capture',
            'title' => 'Todos and inbox',
            'description' => 'Capture tasks quickly, then move them through active, completed, archived, and trash states.',
            'items' => ['Inbox triage', 'Due dates and priorities', 'Bulk lifecycle actions'],
        ],
        [
            'label' => 'Organize',
            'title' => 'Projects, tags, and filters',
            'description' => 'Group work by active projects and tags without leaking another owner workspace into filter results.',
            'items' => ['Project filters', 'Tag filters', 'Saved private views'],
        ],
        [
            'label' => 'Plan',
            'title' => 'Goals and milestones',
            'description' => 'Connect tasks to measurable outcomes with milestone check-ins and honest progress.',
            'items' => ['Dedicated goal creation', 'Dedicated milestone creation', 'Linked task progress'],
        ],
        [
            'label' => 'Repeat',
            'title' => 'Habits and check-ins',
            'description' => 'Track daily and weekly habits, current streaks, best streaks, and linked tasks.',
            'items' => ['Habit creation page', 'Today check-ins', 'Goal-linked habits'],
        ],
        [
            'label' => 'Remember',
            'title' => 'Reminders and notifications',
            'description' => 'Schedule browser-triggered reminders and process due work from the web app.',
            'items' => ['Reminder preferences', 'Due processing', 'Database notifications'],
        ],
        [
            'label' => 'Execute',
            'title' => 'Focus mode and time tracking',
            'description' => 'Work from a short priority set, run focused sessions, and log time against tasks or projects.',
            'items' => ['Focus queue', 'Pomodoro sessions', 'Manual time entries'],
        ],
        [
            'label' => 'Review',
            'title' => 'Calendar, board, and cleanup',
            'description' => 'Inspect work by date, lifecycle column, and cleanup risk without leaving the private workspace.',
            'items' => ['Calendar view', 'Task board', 'Smart cleanup'],
        ],
        [
            'label' => 'Standardize',
            'title' => 'Templates and automations',
            'description' => 'Reuse repeatable task structures and run web-safe automations for common maintenance.',
            'items' => ['Task templates', 'Automation rules', 'Manual web runs'],
        ],
        [
            'label' => 'Secure',
            'title' => 'Account security',
            'description' => 'Protect access with verified accounts, security settings, passkeys, and two-factor controls.',
            'items' => ['Email verification', 'Passkeys', 'Two-factor recovery codes'],
        ],
    ],

    'workflow' => [
        'eyebrow' => 'Operating rhythm',
        'heading' => 'From loose task to finished outcome.',
        'description' => 'Each workflow stage has a focused surface, but the data stays connected so tasks, goals, reminders, habits, and time all describe the same work.',
        'steps' => [
            [
                'stage' => 'Capture',
                'title' => 'Collect the next action.',
                'description' => 'Use todos and inbox views to capture work with enough context to sort later.',
            ],
            [
                'stage' => 'Shape',
                'title' => 'Attach meaning and ownership.',
                'description' => 'Apply projects, tags, saved views, goals, milestones, due dates, priorities, and reminders.',
            ],
            [
                'stage' => 'Focus',
                'title' => 'Work from the right view.',
                'description' => 'Use today, overdue, upcoming, blocked, focus mode, board, calendar, and time tracking views.',
            ],
            [
                'stage' => 'Review',
                'title' => 'Close loops cleanly.',
                'description' => 'Check off habits, process reminders, run cleanup, reopen milestones, and archive completed work.',
            ],
        ],
    ],

    'privacy' => [
        'eyebrow' => 'Private by design',
        'heading' => 'One authenticated owner boundary across the product.',
        'description' => 'The app is built around owner-scoped query boundaries, authenticated dashboards, verified private routes, and web-triggered operations that do not require background workers in the final app behavior.',
        'items' => [
            [
                'title' => 'Owner-scoped dashboards',
                'description' => 'Dashboard counters, task lists, project filters, tag filters, goals, habits, and reminders use the authenticated owner boundary.',
            ],
            [
                'title' => 'Web-safe processing',
                'description' => 'Reminder and maintenance behavior runs from browser actions and app visits instead of cron-only assumptions.',
            ],
            [
                'title' => 'Private route protection',
                'description' => 'Application pages require authentication and verification before private workspace data renders.',
            ],
        ],
    ],

    'cta' => [
        'eyebrow' => 'Start clean',
        'heading' => 'Turn scattered work into one private control deck.',
        'description' => 'Create a workspace, capture tasks, tie them to outcomes, and run the day from focused views.',
        'create' => 'Create workspace',
        'dashboard' => 'Open dashboard',
    ],
];
