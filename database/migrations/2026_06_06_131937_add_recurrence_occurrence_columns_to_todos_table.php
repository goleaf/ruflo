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
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('recurrence_rule_id')
                ->nullable()
                ->after('habit_id')
                ->constrained('todo_recurrence_rules')
                ->nullOnDelete();
            $table->foreignId('recurrence_source_todo_id')
                ->nullable()
                ->after('recurrence_rule_id')
                ->constrained('todos')
                ->nullOnDelete();
            $table->date('recurrence_occurs_on')->nullable()->after('recurrence_source_todo_id');
            $table->unsignedInteger('recurrence_sequence')->nullable()->after('recurrence_occurs_on');

            $table->unique(['user_id', 'recurrence_rule_id', 'recurrence_occurs_on'], 'todos_unique_recurrence_occurrence');
            $table->index(['user_id', 'recurrence_occurs_on'], 'todos_recurrence_calendar_index');
            $table->index(['recurrence_source_todo_id', 'recurrence_occurs_on'], 'todos_recurrence_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropUnique('todos_unique_recurrence_occurrence');
            $table->dropIndex('todos_recurrence_calendar_index');
            $table->dropIndex('todos_recurrence_source_index');
            $table->dropConstrainedForeignId('recurrence_source_todo_id');
            $table->dropConstrainedForeignId('recurrence_rule_id');
            $table->dropColumn(['recurrence_occurs_on', 'recurrence_sequence']);
        });
    }
};
