<?php

return [
    'navigation' => [
        'label' => 'Habits',
    ],

    'pages' => [
        'index' => [
            'title' => 'Habits tracker',
            'description' => 'Track daily and weekly habits with real check-ins, streaks, and linked work.',
        ],
        'create' => [
            'title' => 'Create habit',
            'description' => 'Define a private daily or weekly routine, then return to the tracker for check-ins.',
        ],
    ],

    'summary' => [
        'habits' => 'Habits',
        'checked_today' => 'Checked today',
        'streaks' => 'Streaks',
        'current_streak' => 'Current streak',
        'best_streak' => 'Best streak',
        'linked_tasks' => 'Linked tasks',
    ],

    'tabs' => [
        'habits' => 'Habits',
        'tasks' => 'Linked tasks',
    ],

    'fields' => [
        'title' => 'Habit title',
        'description' => 'Description',
        'frequency' => 'Frequency',
        'target_count' => 'Target',
        'goal' => 'Goal',
        'no_goal' => 'No goal',
        'task' => 'Task',
        'choose_task' => 'Choose a task',
    ],

    'frequency' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
    ],

    'period' => [
        'daily' => 'today',
        'weekly' => 'this week',
    ],

    'placeholders' => [
        'title' => 'Plan the day',
        'description' => 'What does this routine support?',
    ],

    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
        'back_to_habits' => 'Back to habits',
        'open_goals' => 'Open goals',
        'new_habit' => 'New habit',
        'create' => 'Create habit',
        'check_in_today' => 'Check in today',
        'undo_today' => 'Undo today',
        'link_task' => 'Link task',
    ],

    'create' => [
        'heading' => 'Create a habit',
        'description' => 'Habits stay private and can optionally support one active goal.',
    ],

    'badges' => [
        'habit' => 'Habit',
    ],

    'progress' => [
        'label' => 'Habit progress',
        'text' => ':completed of :target check-ins :period (:percent%)',
        'aria' => 'Habit progress is :percent percent',
        'streak' => ':count period(s)',
    ],

    'linked_tasks' => [
        'heading' => 'Linked tasks',
        'completed' => 'Completed',
        'empty' => [
            'title' => 'No linked tasks yet.',
            'description' => 'Choose a task below to connect work to this habit.',
        ],
    ],

    'link' => [
        'heading' => 'Link a task',
        'description' => 'Connect an existing task to this habit without changing the task lifecycle.',
    ],

    'empty' => [
        'title' => 'No habits yet.',
        'description' => 'Create a daily or weekly habit, then check in from this page.',
    ],

    'messages' => [
        'created' => 'Habit created: :title',
        'checked_in' => 'Habit checked in for today.',
        'unchecked' => 'Today check-in removed.',
        'todo_linked' => 'Task linked: :title',
    ],

    'validation' => [
        'title' => 'Enter a habit title up to 120 characters.',
        'description' => 'Keep the habit description under 2,000 characters.',
        'frequency' => 'Choose daily or weekly.',
        'target_count' => 'Enter a target from 1 to 7.',
        'target_daily' => 'Daily habits can only have one check-in per day.',
        'goal_required' => 'Choose one of your active goals.',
        'todo_required' => 'Choose one of your tasks.',
        'active_habit' => 'Archived habits cannot be checked in.',
        'linkable_todo' => 'Only active or completed visible tasks can be linked to habits.',
    ],
];
