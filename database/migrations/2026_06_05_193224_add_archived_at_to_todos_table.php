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
            // Archive is distinct from completion and from deletion: a null
            // archived_at means the task participates in active/completed views;
            // a timestamp removes it to the archive until it is unarchived.
            $table->timestamp('archived_at')->nullable()->after('is_completed');

            // Active/completed list queries filter on (user_id, archived_at);
            // the archive view filters the same pair the other way.
            $table->index(['user_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
