<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    /**
     * Display the workspace creation form.
     */
    public function create(): View
    {
        return view('workspaces.create');
    }

    /**
     * Store a newly created workspace. Creator becomes Owner.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if (isset($validated['logo'])) {
            $validated['logo_path'] = $request->file('logo')->store('workspace-logos', 'public');
        }

        $workspace = null;

        \DB::transaction(function () use (&$workspace, $validated, $request) {
            $workspace = Workspace::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'logo_path' => $validated['logo_path'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()->id,
                'role' => 'owner',
            ]);
        });

        Activity::record($workspace, $request->user(), 'workspace_created');

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'Workspace "'.$workspace->name.'" đã được tạo!');
    }

    /**
     * Display the workspace overview.
     */
    public function show(Request $request, Workspace $workspace): View
    {
        $user = $request->user();

        $projectsQuery = $workspace->projects()
            ->where('status', '!=', 'archived')
            ->withCount(['members', 'tasks'])
            ->latest();

        // Members only see the projects they participate in.
        if (! $user->canManageWorkspace($workspace)) {
            $projectsQuery->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        }

        $projects = $projectsQuery->get();

        $stats = [
            'members' => $workspace->members()->count(),
            'projects' => $workspace->projects()->where('status', '!=', 'archived')->count(),
            'archived' => $workspace->projects()->where('status', 'archived')->count(),
            'my_role' => $user->workspaceRole($workspace),
        ];

        $activities = $workspace->activities()->with('user')->take(8)->get();

        return view('workspaces.show', [
            'workspace' => $workspace,
            'projects' => $projects,
            'stats' => $stats,
            'activities' => $activities,
            'recentTasks' => $workspace->projects()
                ->with(['tasks' => fn ($q) => $q->latest()->take(6)->with('assignee')])
                ->get()
                ->pluck('tasks')
                ->flatten(),
        ]);
    }

    /**
     * Display the workspace settings form (Owner only).
     */
    public function edit(Request $request, Workspace $workspace): View
    {
        return view('workspaces.settings', [
            'workspace' => $workspace,
        ]);
    }

    /**
     * Update workspace info (Owner only).
     */
    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldName = $workspace->name;
        $workspace->update($validated);

        if ($oldName !== $workspace->name) {
            Activity::record($workspace, $request->user(), 'workspace_updated', [
                'old' => $oldName, 'new' => $workspace->name,
            ]);
        }

        return back()->with('status', 'Đã cập nhật workspace.');
    }

    /**
     * Update workspace logo (Owner only).
     */
    public function updateLogo(Request $request, Workspace $workspace): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        if ($workspace->logo_path && Storage::disk('public')->exists($workspace->logo_path)) {
            Storage::disk('public')->delete($workspace->logo_path);
        }

        $workspace->update([
            'logo_path' => $request->file('logo')->store('workspace-logos', 'public'),
        ]);

        return back()->with('status', 'Logo đã được cập nhật.');
    }

    /**
     * Delete the workspace (Owner only).
     */
    public function destroy(Request $request, Workspace $workspace): RedirectResponse
    {
        if ($workspace->logo_path && Storage::disk('public')->exists($workspace->logo_path)) {
            Storage::disk('public')->delete($workspace->logo_path);
        }

        $name = $workspace->name;
        $workspace->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Workspace "'.$name.'" đã bị xóa.');
    }
}
