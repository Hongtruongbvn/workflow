<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'notify_task_assigned',
        'notify_mention',
        'notify_deadline_reminder',
        'notify_project_updates',
    ];

    protected function casts(): array
    {
        return [
            'notify_task_assigned' => 'boolean',
            'notify_mention' => 'boolean',
            'notify_deadline_reminder' => 'boolean',
            'notify_project_updates' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
