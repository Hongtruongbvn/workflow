<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    /**
     * Add a subtask.
     */
    public function store(Request $request, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $position = (int) $task->subtasks()->max('position') + 1;

        $task->subtasks()->create([
            'title' => $validated['title'],
            'position' => $position,
        ]);

        return back()->with('status', 'Đã thêm subtask.');
    }

    /**
     * Toggle a subtask's completion state.
     */
    public function update(Request $request, Project $project, Subtask $subtask): RedirectResponse
    {
        $subtask->update(['is_completed' => ! $subtask->is_completed]);

        if ($subtask->is_completed) {
            Activity::record($project->workspace, $request->user(), 'subtask_completed', [
                'task' => $subtask->task->title,
                'subtask' => $subtask->title,
            ], $project, $subtask->task);
        }

        return back();
    }

    /**
     * Delete a subtask.
     */
    public function destroy(Request $request, Project $project, Subtask $subtask): RedirectResponse
    {
        $subtask->delete();

        return back()->with('status', 'Đã xóa subtask.');
    }
}
