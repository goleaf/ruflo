<?php

use Illuminate\Support\Facades\Schema;

test('workspace performance indexes exist for owner scoped list and seed queries', function () {
    expect(indexNamesFor('projects'))->toContain(
        'projects_owner_name_idx',
        'projects_owner_active_name_idx',
    );

    expect(indexNamesFor('todos'))->toContain(
        'todos_owner_title_idx',
        'todos_owner_state_due_idx',
        'todos_owner_state_updated_idx',
        'todos_owner_project_state_idx',
        'todos_owner_inbox_state_idx',
    );

    expect(indexNamesFor('tag_todo'))->toContain('tag_todo_todo_tag_idx')
        ->and(indexNamesFor('todo_checklist_items'))->toContain('todo_checklist_todo_title_idx')
        ->and(indexNamesFor('goals'))->toContain('goals_owner_title_idx', 'goals_owner_active_order_idx')
        ->and(indexNamesFor('goal_milestones'))->toContain('goal_milestones_goal_title_idx', 'goal_milestones_owner_target_idx')
        ->and(indexNamesFor('habits'))->toContain('habits_owner_title_idx', 'habits_owner_active_title_idx')
        ->and(indexNamesFor('reminders'))->toContain('reminders_owner_recent_idx')
        ->and(indexNamesFor('pomodoro_sessions'))->toContain('pomodoros_owner_active_recent_idx')
        ->and(indexNamesFor('time_entries'))->toContain(
            'time_entries_owner_status_date_idx',
            'time_entries_owner_todo_seed_idx',
            'time_entries_owner_project_seed_idx',
        )
        ->and(indexNamesFor('automation_rule_runs'))->toContain(
            'automation_runs_rule_message_idx',
            'automation_runs_owner_recent_idx',
        );
});

/**
 * @return list<string>
 */
function indexNamesFor(string $table): array
{
    return collect(Schema::getIndexes($table))
        ->pluck('name')
        ->filter()
        ->values()
        ->all();
}
