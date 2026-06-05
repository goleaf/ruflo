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
            // Tasks may belong to one project; deleting the project orphans the
            // task to "no project" rather than destroying it.
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('priority', 20)->default('normal')->after('title');
            $table->date('due_date')->nullable()->after('priority');

            $table->index(['user_id', 'project_id']);
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['user_id', 'project_id']);
            $table->dropIndex(['user_id', 'due_date']);
            $table->dropIndex(['user_id', 'priority']);
            $table->dropColumn(['project_id', 'priority', 'due_date']);
        });
    }
};
