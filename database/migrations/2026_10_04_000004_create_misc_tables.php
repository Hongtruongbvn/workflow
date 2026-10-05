<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // created, updated, assigned, status_changed, commented, uploaded...
            $table->json('data')->nullable(); // extra info: old_status, new_status, etc.
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });

        Schema::create('recently_vieweds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('viewable'); // viewable_type, viewable_id (Project or Task)
            $table->timestamp('viewed_at');

            $table->index(['user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_vieweds');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('activities');
    }
};
