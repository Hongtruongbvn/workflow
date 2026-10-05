<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TrashController extends Controller
{
    /**
     * List soft-deleted tasks in workspaces the user manages.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $managedWorkspaceIds = $user->workspaceMemberships()
            ->whereIn('role', ['owner', 'manager'])
            ->pluck('workspace_id');

        $trashedTasks = Task::onlyTrashed()
            ->whereIn('project_id', function ($query) use ($managedWorkspaceIds) {
                $query->select('id')
                    ->from('projects')
                    ->whereIn('workspace_id', $managedWorkspaceIds);
            })
            ->with(['project.workspace', 'creator'])
            ->latest('deleted_at')
            ->get();

        return view('trash.index', [
            'tasks' => $trashedTasks,
        ]);
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(Request $request, int $taskId): RedirectResponse
    {
        $task = Task::onlyTrashed()->findOrFail($taskId);
        $project = $task->project()->withTrashed()->first();

        if (! $request->user()->canManageWorkspace($project->workspace)) {
            abort(403);
        }

        $task->restore();

        Activity::record($project->workspace, $request->user(), 'task_restored', [
            'task' => $task->title,
        ], $project, $task);

        return back()->with('status', 'Đã khôi phục task "'.$task->title.'".');
    }

    /**
     * Permanently delete a task.
     */
    public function destroy(Request $request, int $taskId): RedirectResponse
    {
        $task = Task::onlyTrashed()->with(['comments', 'attachments'])->findOrFail($taskId);
        $project = $task->project()->withTrashed()->first();

        if (! $request->user()->canManageWorkspace($project->workspace)) {
            abort(403);
        }

        // Remove stored attachment files.
        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $title = $task->title;
        $task->forceDelete();

        return back()->with('status', 'Đã xóa vĩnh viễn task "'.$title.'".');
    }
}
