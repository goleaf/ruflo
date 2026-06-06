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
        Schema::create('todo_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('kind', 20)->default('task');
            $table->string('visibility', 20)->default('private');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->unsignedSmallInteger('due_offset_days')->nullable();
            $table->string('project_name', 120)->nullable();
            $table->json('checklist_items');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'kind']);
            $table->index(['user_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_templates');
    }
};
