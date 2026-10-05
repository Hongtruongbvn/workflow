<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'milestone_id', 'created_by', 'assignee_id',
        'title', 'description', 'status', 'priority', 'due_date', 'position',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public const STATUSES = ['todo', 'in_progress', 'review', 'done'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === 'done' ? now() : null;
            }
        });

        // Keep the parent milestone's completion state in sync.
        static::saved(function (Task $task) {
            if (! $task->milestone_id) {
                return;
            }

            $milestone = $task->milestone()->first();

            if (! $milestone) {
                return;
            }

            $hasOpenTasks = $milestone->tasks()->where('status', '!=', 'done')->exists();

            if (! $hasOpenTasks && ! $milestone->completed_at) {
                $milestone->update(['completed_at' => now()]);

                Activity::record(
                    $task->project->workspace,
                    $task->assignee ?? $task->creator ?? User::find($task->created_by),
                    'milestone_completed',
                    ['milestone' => $milestone->name],
                    $task->project,
                    $task,
                );
            } elseif ($hasOpenTasks && $milestone->completed_at) {
                $milestone->update(['completed_at' => null]);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class)->orderBy('position');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_labels')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------ */

    public function isOverdue(): bool
    {
        return $this->status !== 'done'
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->status !== 'done'
            && $this->due_date !== null
            && $this->due_date->isToday();
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function dueLabel(): string
    {
        if ($this->status === 'done') {
            return 'Completed';
        }

        if (! $this->due_date) {
            return 'No due date';
        }

        if ($this->due_date->isPast()) {
            return 'Overdue';
        }

        if ($this->due_date->isToday()) {
            return 'Due today';
        }

        if ($this->due_date->isTomorrow()) {
            return 'Due tomorrow';
        }

        return 'Due '.$this->due_date->format('d/m/Y');
    }

    public function subtaskProgress(): array
    {
        $total = $this->subtasks()->count();
        $done = $this->subtasks()->where('is_completed', true)->count();

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }
}
