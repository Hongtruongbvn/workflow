<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'title' => 'Bạn được giao task mới',
            'message' => $this->assignedBy->name.' đã giao task "'.$this->task->title.'" cho bạn',
            'project_id' => $this->task->project_id,
            'task_id' => $this->task->id,
            'link' => route('projects.board', $this->task->project_id),
        ];
    }
}
