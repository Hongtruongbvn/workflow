<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'workspace_id', 'project_id', 'task_id', 'user_id', 'type', 'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public const TYPES = [
        'workspace_created', 'workspace_updated', 'member_joined', 'member_removed', 'role_changed',
        'project_created', 'project_updated', 'project_archived', 'project_restored',
        'task_created', 'task_updated', 'task_assigned', 'task_status_changed',
        'task_completed', 'task_deleted', 'task_restored', 'commented', 'uploaded',
        'subtask_completed', 'milestone_created', 'milestone_completed',
    ];

    /**
     * Convenience helper to log an activity.
     */
    public static function record(
        Workspace $workspace,
        User $user,
        string $type,
        array $data = [],
        ?Project $project = null,
        ?Task $task = null,
    ): self {
        return static::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project?->id,
            'task_id' => $task?->id,
            'user_id' => $user->id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
