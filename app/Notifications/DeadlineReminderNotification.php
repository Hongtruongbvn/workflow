<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $kind, // due_tomorrow | overdue
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deadline_'.$this->kind,
            'kind' => $this->kind,
            'task_id' => $this->task->id,
            'title' => $this->kind === 'overdue' ? '⚠ Task quá hạn' : '⏰ Task sắp đến hạn',
            'message' => $this->kind === 'overdue'
                ? '"'.$this->task->title.'" đã quá hạn ('.$this->task->due_date->format('d/m/Y').')'
                : '"'.$this->task->title.'" sẽ đến hạn vào ngày mai',
            'project_id' => $this->task->project_id,
            'link' => route('tasks.show', [$this->task->project_id, $this->task->id]),
        ];
    }
}
