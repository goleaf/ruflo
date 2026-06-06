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
            $table->foreignId('goal_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->foreignId('goal_milestone_id')->nullable()->after('goal_id')->constrained()->nullOnDelete();

            $table->index(['user_id', 'goal_id']);
            $table->index(['user_id', 'goal_milestone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'goal_milestone_id']);
            $table->dropIndex(['user_id', 'goal_id']);
            $table->dropConstrainedForeignId('goal_milestone_id');
            $table->dropConstrainedForeignId('goal_id');
        });
    }
};
