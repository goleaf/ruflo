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
        Schema::create('todo_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->foreignId('depends_on_todo_id')->constrained('todos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'todo_id', 'depends_on_todo_id'], 'todo_dependencies_unique_link');
            $table->index(['user_id', 'todo_id']);
            $table->index(['user_id', 'depends_on_todo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_dependencies');
    }
};
