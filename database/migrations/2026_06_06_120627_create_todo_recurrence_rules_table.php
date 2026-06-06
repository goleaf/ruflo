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
        Schema::create('todo_recurrence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_id')->constrained()->cascadeOnDelete();
            $table->string('frequency', 20);
            $table->unsignedTinyInteger('interval')->default(1);
            $table->date('starts_on');
            $table->json('weekdays')->nullable();
            $table->unsignedTinyInteger('month_day')->nullable();
            $table->string('end_type', 20)->default('never');
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('max_occurrences')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->date('last_generated_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'todo_id'], 'todo_recurrence_rules_unique_task');
            $table->index(['user_id', 'is_enabled', 'starts_on'], 'todo_recurrence_rules_active_index');
            $table->index(['user_id', 'frequency']);
            $table->index(['todo_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_recurrence_rules');
    }
};
