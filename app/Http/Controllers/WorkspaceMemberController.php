<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceMemberController extends Controller
{
    /**
     * Display the members page.
     */
    public function index(Request $request, Workspace $workspace): View
    {
        $members = $workspace->members()
            ->withPivot('id', 'role', 'created_at')
            ->orderByRaw("CASE pivot_role WHEN 'owner' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();

        // Count of workspace projects each member participates in.
        $projectCounts = $workspace->projects()->pluck('id');
        $memberProjectCounts = [];

        foreach ($members as $member) {
            $memberProjectCounts[$member->id] = Project::whereIn('projects.id', $projectCounts)
                ->whereHas('members', fn ($q) => $q->where('users.id', $member->id))
                ->count();
        }

        $invitations = $workspace->invitations()
            ->where('status', 'pending')
            ->with('inviter')
            ->latest()
            ->get();

        return view('workspaces.members', [
            'workspace' => $workspace,
            'members' => $members,
            'memberProjectCounts' => $memberProjectCounts,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Change a member's role (Owner only).
     */
    public function updateRole(Request $request, Workspace $workspace, User $member): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:manager,member'],
        ]);

        $membership = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        // Cannot change the owner's role, and owner is the only one allowed to manage roles.
        if ($membership->role === 'owner') {
            return back()->with('error', 'Không thể thay đổi quyền của Owner.');
        }

        if ($membership->user_id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể thay đổi quyền của chính mình.');
        }

        $oldRole = $membership->role;
        $membership->update(['role' => $validated['role']]);

        Activity::record($workspace, $request->user(), 'role_changed', [
            'member' => $member->name,
            'old' => $oldRole,
            'new' => $validated['role'],
        ]);

        return back()->with('status', 'Đã đổi quyền của '.$member->name.' thành '.$validated['role'].'.');
    }

    /**
     * Remove a member from the workspace (Owner only).
     */
    public function destroy(Request $request, Workspace $workspace, User $member): RedirectResponse
    {
        $membership = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        if ($membership->role === 'owner') {
            return back()->with('error', 'Không thể xóa Owner khỏi workspace.');
        }

        if ($membership->user_id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể xóa chính mình.');
        }

        $membership->delete();

        Activity::record($workspace, $request->user(), 'member_removed', [
            'member' => $member->name,
        ]);

        return back()->with('status', 'Đã xóa '.$member->name.' khỏi workspace.');
    }
}
