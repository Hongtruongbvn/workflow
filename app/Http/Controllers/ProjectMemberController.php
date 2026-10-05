<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    /**
     * Add a workspace member to the project.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Must be a workspace member to join the project.
        if (! $project->workspace->members()->where('users.id', $user->id)->exists()) {
            return back()->with('error', $user->name.' không phải thành viên của workspace.');
        }

        if ($project->members()->where('users.id', $user->id)->exists()) {
            return back()->with('error', $user->name.' đã tham gia project này.');
        }

        $project->members()->attach($user->id);

        return back()->with('status', 'Đã thêm '.$user->name.' vào project.');
    }

    /**
     * Remove a member from the project.
     */
    public function destroy(Request $request, Project $project, User $member): RedirectResponse
    {
        // Unassign their tasks in this project as well.
        $project->tasks()->where('assignee_id', $member->id)->update(['assignee_id' => null]);

        $project->members()->detach($member->id);

        return back()->with('status', 'Đã xóa '.$member->name.' khỏi project.');
    }
}
