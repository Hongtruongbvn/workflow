<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecentlyViewed extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'viewable_type', 'viewable_id', 'viewed_at'];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Track that a user just viewed a model (project or task).
     */
    public static function record(User $user, Model $model): void
    {
        static::updateOrCreate(
            [
                'user_id' => $user->id,
                'viewable_type' => $model->getMorphClass(),
                'viewable_id' => $model->id,
            ],
            ['viewed_at' => now()]
        );
    }

    /**
     * Human-friendly type label.
     */
    public function typeLabel(): string
    {
        return str_contains($this->viewable_type, 'Task') ? '📋' : '📁';
    }
}
