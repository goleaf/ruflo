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
        Schema::create('todo_recurrence_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_recurrence_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_id')->nullable()->constrained('todos')->nullOnDelete();
            $table->string('type', 24);
            $table->date('original_occurs_on');
            $table->date('adjusted_occurs_on')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'todo_recurrence_rule_id', 'original_occurs_on'], 'todo_recurrence_exceptions_unique_date');
            $table->index(['user_id', 'type'], 'todo_recurrence_exceptions_user_type_index');
            $table->index(['todo_id', 'type'], 'todo_recurrence_exceptions_todo_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_recurrence_exceptions');
    }
};
