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
            $table->foreignId('habit_id')
                ->nullable()
                ->after('goal_milestone_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['user_id', 'habit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'habit_id']);
            $table->dropConstrainedForeignId('habit_id');
        });
    }
};
