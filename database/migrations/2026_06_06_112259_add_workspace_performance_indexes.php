<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['user_id', 'name'], 'projects_owner_name_idx');
            $table->index(['user_id', 'archived_at', 'name'], 'projects_owner_active_name_idx');
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->index(['user_id', 'title'], 'todos_owner_title_idx');
            $table->index(['user_id', 'deleted_at', 'archived_at', 'is_completed', 'due_date', 'created_at'], 'todos_owner_state_due_idx');
            $table->index(['user_id', 'deleted_at', 'archived_at', 'is_completed', 'updated_at'], 'todos_owner_state_updated_idx');
            $table->index(['user_id', 'project_id', 'deleted_at', 'archived_at', 'is_completed'], 'todos_owner_project_state_idx');
            $table->index(['user_id', 'deleted_at', 'inbox_captured_at'], 'todos_owner_inbox_state_idx');
        });

        Schema::table('tag_todo', function (Blueprint $table) {
            $table->index(['todo_id', 'tag_id'], 'tag_todo_todo_tag_idx');
        });

        Schema::table('todo_checklist_items', function (Blueprint $table) {
            $table->index(['todo_id', 'title'], 'todo_checklist_todo_title_idx');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->index(['user_id', 'title'], 'goals_owner_title_idx');
            $table->index(['user_id', 'archived_at', 'completed_at', 'target_date', 'updated_at'], 'goals_owner_active_order_idx');
        });

        Schema::table('goal_milestones', function (Blueprint $table) {
            $table->index(['goal_id', 'title'], 'goal_milestones_goal_title_idx');
            $table->index(['user_id', 'target_date'], 'goal_milestones_owner_target_idx');
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->index(['user_id', 'title'], 'habits_owner_title_idx');
            $table->index(['user_id', 'archived_at', 'title'], 'habits_owner_active_title_idx');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->index(['user_id', 'remind_at', 'id'], 'reminders_owner_recent_idx');
        });

        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'updated_at', 'id'], 'pomodoros_owner_active_recent_idx');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'entry_date', 'updated_at'], 'time_entries_owner_status_date_idx');
            $table->index(['user_id', 'todo_id', 'entry_date', 'source'], 'time_entries_owner_todo_seed_idx');
            $table->index(['user_id', 'project_id', 'entry_date', 'source'], 'time_entries_owner_project_seed_idx');
        });

        Schema::table('automation_rule_runs', function (Blueprint $table) {
            $table->index(['automation_rule_id', 'message'], 'automation_runs_rule_message_idx');
            $table->index(['user_id', 'created_at'], 'automation_runs_owner_recent_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_rule_runs', function (Blueprint $table) {
            $table->dropIndex('automation_runs_owner_recent_idx');
            $table->dropIndex('automation_runs_rule_message_idx');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex('time_entries_owner_project_seed_idx');
            $table->dropIndex('time_entries_owner_todo_seed_idx');
            $table->dropIndex('time_entries_owner_status_date_idx');
        });

        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            $table->dropIndex('pomodoros_owner_active_recent_idx');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_owner_recent_idx');
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->dropIndex('habits_owner_active_title_idx');
            $table->dropIndex('habits_owner_title_idx');
        });

        Schema::table('goal_milestones', function (Blueprint $table) {
            $table->dropIndex('goal_milestones_owner_target_idx');
            $table->dropIndex('goal_milestones_goal_title_idx');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex('goals_owner_active_order_idx');
            $table->dropIndex('goals_owner_title_idx');
        });

        Schema::table('todo_checklist_items', function (Blueprint $table) {
            $table->dropIndex('todo_checklist_todo_title_idx');
        });

        Schema::table('tag_todo', function (Blueprint $table) {
            $table->dropIndex('tag_todo_todo_tag_idx');
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('todos_owner_inbox_state_idx');
            $table->dropIndex('todos_owner_project_state_idx');
            $table->dropIndex('todos_owner_state_updated_idx');
            $table->dropIndex('todos_owner_state_due_idx');
            $table->dropIndex('todos_owner_title_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_owner_active_name_idx');
            $table->dropIndex('projects_owner_name_idx');
        });
    }
};
