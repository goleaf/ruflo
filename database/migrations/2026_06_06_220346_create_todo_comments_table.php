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
        Schema::create('todo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'todo_id', 'deleted_at', 'created_at'], 'todo_comments_task_feed_idx');
            $table->index(['author_id', 'created_at'], 'todo_comments_author_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_comments');
    }
};
