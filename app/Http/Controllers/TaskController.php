<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\RecentlyViewed;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display the task detail page.
     */
    public function show(Request $request, Project $project, Task $task): View
    {
        RecentlyViewed::record($request->user(), $task);

        $task->load([
            'assignee', 'creator', 'labels', 'subtasks', 'milestone',
            'comments.user', 'comments.replies.user',
            'attachments.user',
        ]);

        return view('tasks.show', [
            'project' => $project,
            'task' => $task,
            'subtaskProgress' => $task->subtaskProgress(),
            'activities' => $task->activities()->with('user')->take(10)->get(),
            'projectMembers' => $project->members()->orderBy('name')->get(),
            'workspaceLabels' => $project->workspace->labels()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the task (manager, assignee or creator).
     */
    public function update(Request $request, Project $project, Task $task): RedirectResponse
    {
        if (! $this->canModify($request->user(), $project, $task)) {
            return back()->with('error', 'Bạn không có quyền sửa task này.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Task::PRIORITIES)],
            'assignee_id' => ['nullable', 'in:'.$project->members()->pluck('users.id')->implode(',')],
            'due_date' => ['nullable', 'date'],
            'milestone_id' => [
                'nullable',
                'in:'.$project->milestones()->pluck('id')->implode(','),
            ],
        ]);

        $oldAssignee = $task->assignee_id;
        $oldStatus = $task->status;
        $task->update($validated);

        if ($task->status !== $oldStatus) {
            Activity::record($project->workspace, $request->user(), 'task_status_changed', [
                'task' => $task->title, 'old' => $oldStatus, 'new' => $task->status,
            ], $project, $task);
        } else {
            Activity::record($project->workspace, $request->user(), 'task_updated', [
                'task' => $task->title,
            ], $project, $task);
        }

        // New assignee gets a notification.
        if ($task->assignee_id && $task->assignee_id !== $oldAssignee
            && $task->assignee->wantsNotification('notify_task_assigned')) {
            $task->assignee->notify(new TaskAssignedNotification($task, $request->user()));

            Activity::record($project->workspace, $request->user(), 'task_assigned', [
                'task' => $task->title, 'assignee' => $task->assignee->name,
            ], $project, $task);
        }

        return redirect()
            ->route('tasks.show', [$project, $task])
            ->with('status', 'Đã cập nhật task.');
    }

    /**
     * Soft-delete the task (goes to Trash, restorable in a later phase).
     */
    public function destroy(Request $request, Project $project, Task $task): RedirectResponse
    {
        if (! $request->user()->canManageWorkspace($project->workspace)) {
            return back()->with('error', 'Chỉ Owner/Manager mới có thể xóa task.');
        }

        $title = $task->title;
        $task->delete(); // soft delete

        Activity::record($project->workspace, $request->user(), 'task_deleted', [
            'task' => $title,
        ], $project);

        return redirect()
            ->route('projects.board', $project)
            ->with('status', 'Task "'.$title.'" đã chuyển vào Trash.');
    }

    /* ------------------------------------------------------------------
     |  Kanban
     * ------------------------------------------------------------------ */

    /**
     * Store a newly created task (from the board's quick-create form).
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Task::PRIORITIES)],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        // Assignee must be a project member.
        if (! empty($validated['assignee_id'])) {
            $assignee = User::find($validated['assignee_id']);

            if (! $assignee || ! $project->members()->where('users.id', $assignee->id)->exists()) {
                return back()->with('error', 'Assignee phải là thành viên của project.');
            }
        }

        $position = (int) $project->tasks()->where('status', $validated['status'])->max('position') + 1;

        $task = $project->tasks()->create([
            ...$validated,
            'created_by' => $request->user()->id,
            'position' => $position,
        ]);

        if (! empty($task->assignee_id) && $task->assignee->wantsNotification('notify_task_assigned')) {
            $task->assignee->notify(new TaskAssignedNotification($task, $request->user()));
        }

        Activity::record($project->workspace, $request->user(), 'task_created', [
            'task' => $task->title,
        ], $project, $task);

        return redirect()
            ->route('projects.board', $project)
            ->with('status', 'Task "'.$task->title.'" đã được tạo!');
    }

    /**
     * Move a task to another kanban column (drag & drop).
     */
    public function move(Request $request, Project $project, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:tasks,id'],
        ]);

        if (! $this->canModify($request->user(), $project, $task)) {
            return response()->json(['message' => 'Bạn chỉ có thể di chuyển task của mình.'], 403);
        }

        $oldStatus = $task->status;

        // Reorder the other tasks in the destination column (keep their real slot).
        foreach ($validated['order'] as $index => $taskId) {
            if ((int) $taskId === $task->id) {
                continue;
            }

            Task::where('project_id', $project->id)
                ->whereKey($taskId)
                ->update(['status' => $validated['status'], 'position' => $index + 1]);
        }

        // Save the moved task through the model so the completed_at hook fires.
        $movedIndex = array_search($task->id, $validated['order'], true);
        $task->status = $validated['status'];
        $task->position = $movedIndex === false ? $task->position : $movedIndex + 1;
        $task->save();

        if ($oldStatus !== $task->status) {
            Activity::record($project->workspace, $request->user(), 'task_status_changed', [
                'task' => $task->title,
                'old' => $oldStatus,
                'new' => $task->status,
            ], $project, $task);
        }

        return response()->json([
            'ok' => true,
            'status' => $task->status,
            'completed_at' => $task->completed_at?->toISOString(),
        ]);
    }

    /**
     * Manager+ can modify everything; assignee and creator can move/update their tasks.
     */
    private function canModify(User $user, Project $project, Task $task): bool
    {
        if ($user->canManageWorkspace($project->workspace)) {
            return true;
        }

        return $task->assignee_id === $user->id || $task->created_by === $user->id;
    }
}
