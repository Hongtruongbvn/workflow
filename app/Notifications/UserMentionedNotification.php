<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'title' => 'Bạn được nhắc đến trong bình luận',
            'message' => $this->comment->user->name.' đã nhắc đến bạn trong task "'.$this->task->title.'"',
            'project_id' => $this->task->project_id,
            'task_id' => $this->task->id,
            'link' => route('tasks.show', [$this->task->project_id, $this->task->id]),
        ];
    }
}
