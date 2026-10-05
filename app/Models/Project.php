<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'created_by', 'name', 'description',
        'start_date', 'due_date', 'status', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public const STATUSES = ['planning', 'active', 'on_hold', 'completed', 'archived'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /* ------------------------------------------------------------------
     |  Statistics helpers
     * ------------------------------------------------------------------ */

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', 'archived');
    }

    public function taskStats(): array
    {
        $tasks = $this->tasks();

        return [
            'total' => (clone $tasks)->count(),
            'todo' => (clone $tasks)->where('status', 'todo')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'review' => (clone $tasks)->where('status', 'review')->count(),
            'done' => (clone $tasks)->where('status', 'done')->count(),
            'overdue' => (clone $tasks)
                ->where('status', '!=', 'done')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    public function progressPercent(): int
    {
        $stats = $this->taskStats();

        if ($stats['total'] === 0) {
            return 0;
        }

        return (int) round($stats['done'] / $stats['total'] * 100);
    }
}
