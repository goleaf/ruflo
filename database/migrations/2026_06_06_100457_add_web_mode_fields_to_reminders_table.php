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
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('todo_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('remind_at')->nullable()->after('todo_id');
            $table->string('status', 20)->default('pending')->after('remind_at');
            $table->timestamp('processed_at')->nullable()->after('status');
            $table->timestamp('skipped_at')->nullable()->after('processed_at');
            $table->string('skipped_reason')->nullable()->after('skipped_at');
            $table->text('last_error')->nullable()->after('skipped_reason');

            $table->unique(['user_id', 'todo_id']);
            $table->index(['user_id', 'status', 'remind_at']);
            $table->index(['todo_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'todo_id']);
            $table->dropIndex(['user_id', 'status', 'remind_at']);
            $table->dropIndex(['todo_id', 'status']);
            $table->dropConstrainedForeignId('todo_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'remind_at',
                'status',
                'processed_at',
                'skipped_at',
                'skipped_reason',
                'last_error',
            ]);
        });
    }
};
