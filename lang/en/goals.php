<?php

return [
    'navigation' => [
        'label' => 'Goals',
    ],

    'pages' => [
        'index' => [
            'title' => 'Goals and milestones',
            'description' => 'Connect private tasks and projects to measurable outcomes without fake progress.',
        ],
    ],

    'summary' => [
        'goals' => 'Goals',
        'tasks' => 'Tasks',
        'milestones' => 'Milestones',
        'linkable_tasks' => 'Linkable tasks',
    ],

    'tabs' => [
        'goals' => 'Goals',
        'create' => 'Create goal',
        'milestones' => 'Add milestone',
    ],

    'fields' => [
        'goal' => 'Goal',
        'choose_goal' => 'Choose a goal',
        'title' => 'Goal title',
        'description' => 'Description',
        'project' => 'Project',
        'no_project' => 'No project',
        'target_date' => 'Target date',
        'milestone' => 'Milestone',
        'milestone_title' => 'Milestone title',
        'task' => 'Task',
        'choose_task' => 'Choose a task',
        'whole_goal' => 'Whole goal',
    ],

    'placeholders' => [
        'title' => 'Launch the next useful outcome',
        'description' => 'What changes when this goal is done?',
        'milestone_title' => 'Define the next milestone',
    ],

    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
        'open_tasks' => 'Open tasks',
        'create' => 'Create goal',
        'add_milestone' => 'Add milestone',
        'check_in' => 'Check in',
        'reopen_milestone' => 'Reopen',
        'link_task' => 'Link task',
    ],

    'create' => [
        'heading' => 'Create a goal',
        'description' => 'Goals stay private and may be tied to one active project.',
    ],

    'badges' => [
        'goal' => 'Goal',
    ],

    'target_date' => 'Target :date',

    'progress' => [
        'label' => 'Goal progress',
        'text' => ':completed of :total units complete (:percent%)',
        'aria' => 'Goal progress is :percent percent',
        'counts' => ':completed / :total',
    ],

    'milestones' => [
        'heading' => 'Milestones',
        'create_heading' => 'Add a milestone',
        'create_description' => 'Milestones are check-in points inside one goal.',
        'checked_in' => 'Checked in',
        'linked_tasks' => ':count linked task(s)',
        'empty' => [
            'title' => 'No milestones yet.',
            'description' => 'Add the first check-in point for this goal.',
        ],
    ],

    'link' => [
        'heading' => 'Link a task',
        'description' => 'Link an existing task to the whole goal or a milestone.',
    ],

    'empty' => [
        'title' => 'No goals yet.',
        'description' => 'Create a goal, add milestones, then link existing tasks for honest progress.',
    ],

    'messages' => [
        'created' => 'Goal created: :title',
        'milestone_created' => 'Milestone added: :title',
        'milestone_checked_in' => 'Milestone checked in.',
        'milestone_reopened' => 'Milestone reopened.',
        'todo_linked' => 'Task linked: :title',
    ],

    'validation' => [
        'title' => 'Enter a goal title up to 120 characters.',
        'description' => 'Keep the goal description under 2,000 characters.',
        'milestone_title' => 'Enter a milestone title up to 120 characters.',
        'target_date' => 'Enter a valid target date.',
        'goal_required' => 'Choose one of your goals.',
        'todo_required' => 'Choose one of your tasks.',
        'milestone_goal' => 'Choose a milestone that belongs to this goal.',
        'linkable_todo' => 'Only active or completed visible tasks can be linked to goals.',
    ],
];
