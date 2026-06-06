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
        Schema::create('todo_comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('handle', 80);
            $table->timestamps();

            $table->unique(['todo_comment_id', 'mentioned_user_id'], 'todo_comment_mentions_comment_user_unique');
            $table->index(['user_id', 'mentioned_user_id', 'created_at'], 'todo_comment_mentions_owner_target_idx');
            $table->index(['mentioned_user_id', 'created_at'], 'todo_comment_mentions_target_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_comment_mentions');
    }
};
