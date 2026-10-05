<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    /**
     * Create a workspace label.
     */
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $existing = $workspace->labels()->where('name', $validated['name'])->first();

        if ($existing) {
            return back()->with('error', 'Label "'.$validated['name'].'" đã tồn tại.');
        }

        $workspace->labels()->create($validated);

        return back()->with('status', 'Đã tạo label "'.$validated['name'].'".');
    }

    /**
     * Delete a workspace label (detaches from all tasks).
     */
    public function destroy(Request $request, Label $label): RedirectResponse
    {
        $name = $label->name;
        $label->delete();

        return back()->with('status', 'Đã xóa label "'.$name.'".');
    }

    /**
     * Attach a label to a task.
     */
    public function attach(Request $request, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'label_id' => ['required', 'exists:labels,id'],
        ]);

        $label = Label::findOrFail($validated['label_id']);

        // Label must belong to the task's workspace.
        if ($label->workspace_id !== $project->workspace_id) {
            return back()->with('error', 'Label không thuộc workspace này.');
        }

        $task->labels()->syncWithoutDetaching([$label->id]);

        return back()->with('status', 'Đã gắn label "'.$label->name.'".');
    }

    /**
     * Detach a label from a task.
     */
    public function detach(Request $request, Project $project, Task $task, Label $label): RedirectResponse
    {
        $task->labels()->detach([$label->id]);

        return back()->with('status', 'Đã gỡ label "'.$label->name.'".');
    }
}
