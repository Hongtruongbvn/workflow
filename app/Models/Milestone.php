<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $fillable = ['project_id', 'name', 'description', 'due_date', 'completed_at'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function progressPercent(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round($this->tasks()->where('status', 'done')->count() / $total * 100);
    }
}
