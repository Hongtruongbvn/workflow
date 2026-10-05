<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Milestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    /**
     * Store a new milestone.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $project->milestones()->create($validated);

        Activity::record($project->workspace, $request->user(), 'milestone_created', [
            'milestone' => $validated['name'],
        ], $project);

        return back()->with('status', 'Đã tạo milestone "'.$validated['name'].'".');
    }

    /**
     * Update a milestone.
     */
    public function update(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $milestone->update($validated);

        return back()->with('status', 'Đã cập nhật milestone.');
    }

    /**
     * Delete a milestone (tasks are kept, milestone_id becomes null).
     */
    public function destroy(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $name = $milestone->name;
        $milestone->delete();

        return back()->with('status', 'Đã xóa milestone "'.$name.'" (các task được giữ lại).');
    }
}
