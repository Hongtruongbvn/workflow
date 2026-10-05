<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'notifications:deadline-reminders';

    protected $description = 'Gửi reminder cho task due tomorrow và task quá hạn (tránh gửi trùng trong ngày)';

    public function handle(): int
    {
        $sent = 0;

        // --- Tasks due tomorrow ---
        $dueTomorrow = Task::query()
            ->where('status', '!=', 'done')
            ->whereDate('due_date', now()->addDay()->toDateString())
            ->whereNotNull('assignee_id')
            ->with('assignee')
            ->get();

        foreach ($dueTomorrow as $task) {
            $sent += $this->notifyOnce($task->assignee, $task, 'due_tomorrow');
        }

        // --- Overdue tasks ---
        $overdue = Task::query()
            ->overdue()
            ->whereNotNull('assignee_id')
            ->with('assignee')
            ->get();

        foreach ($overdue as $task) {
            $sent += $this->notifyOnce($task->assignee, $task, 'overdue');
        }

        $this->info("Đã gửi {$sent} deadline reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Notify the assignee unless they already got the same reminder today.
     */
    private function notifyOnce(User $user, Task $task, string $kind): int
    {
        $alreadySent = $user->notifications()
            ->where('type', DeadlineReminderNotification::class)
            ->where('data->task_id', $task->id)
            ->where('data->kind', $kind)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent || ! $user->wantsNotification('notify_deadline_reminder')) {
            return 0;
        }

        $user->notify(new DeadlineReminderNotification($task, $kind));

        return 1;
    }
}
