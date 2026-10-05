<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\RecentlyViewed;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display the project creation form.
     */
    public function create(Workspace $workspace): View
    {
        return view('projects.create', [
            'workspace' => $workspace,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', Project::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Project::PRIORITIES)],
            'members' => ['array'],
            'members.*' => ['exists:users,id'],
        ]);

        $project = $workspace->projects()->create([
            ...collect($validated)->except('members')->all(),
            'created_by' => $request->user()->id,
        ]);

        // Attach selected workspace members (+ the creator).
        $memberIds = collect($validated['members'] ?? [])
            ->push($request->user()->id)
            ->unique()
            ->filter(fn ($id) => $workspace->members()->where('users.id', $id)->exists());

        $project->members()->attach($memberIds);

        Activity::record($workspace, $request->user(), 'project_created', [
            'project' => $project->name,
        ], $project);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project "'.$project->name.'" đã được tạo!');
    }

    /**
     * Project dashboard.
     */
    public function show(Request $request, Project $project): View
    {
        RecentlyViewed::record($request->user(), $project);

        $project->load('members');

        $stats = $project->taskStats();
        $recentCompleted = $project->tasks()->where('status', 'done')->latest('completed_at')->take(5)->get();
        $overdue = $project->tasks()->overdue()->with('assignee')->take(5)->get();

        $milestones = $project->milestones()
            ->withCount(['tasks', 'tasks as tasks_done' => fn ($q) => $q->where('status', 'done')])
            ->orderBy('due_date')
            ->get();

        return view('projects.show', [
            'project' => $project,
            'stats' => $stats,
            'progress' => $project->progressPercent(),
            'recentCompleted' => $recentCompleted,
            'overdue' => $overdue,
            'milestones' => $milestones,
            'isFavorite' => $project->favorites()->where('user_id', $request->user()->id)->exists(),
        ]);
    }

    /**
     * Kanban board with optional filtering and search.
     */
    public function board(Request $request, Project $project): View
    {
        $query = $project->tasks()->with(['assignee', 'labels']);

        // --- Search: title or description ---
        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $query->where(function ($w) use ($term) {
                $w->where('title', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        // --- Priority ---
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // --- Assignee (id, "unassigned" or "me") ---
        if ($request->filled('assignee')) {
            match ($request->assignee) {
                'unassigned' => $query->whereNull('assignee_id'),
                'me' => $query->where('assignee_id', $request->user()->id),
                default => $query->where('assignee_id', $request->assignee),
            };
        }

        // --- Label ---
        if ($request->filled('label')) {
            $query->whereHas('labels', fn ($l) => $l->where('labels.id', $request->label));
        }

        // --- Deadline ---
        if ($request->filled('due')) {
            match ($request->due) {
                'overdue' => $query->overdue(),
                'today' => $query->whereDate('due_date', today())->where('status', '!=', 'done'),
                'week' => $query->whereBetween('due_date', [today(), today()->addDays(7)])->where('status', '!=', 'done'),
                'none' => $query->whereNull('due_date'),
                default => null,
            };
        }

        $tasks = $query->orderBy('position')->get()->groupBy('status');

        return view('projects.board', [
            'project' => $project,
            'columns' => \App\Models\Task::STATUSES,
            'tasksByStatus' => $tasks,
            'projectMembers' => $project->members()->orderBy('name')->get(),
            'workspaceLabels' => $project->workspace->labels()->orderBy('name')->get(),
            'filters' => $request->only(['q', 'priority', 'assignee', 'label', 'due']),
        ]);
    }

    /**
     * Project settings (edit info + members).
     */
    public function edit(Request $request, Project $project): View
    {
        $workspaceMembers = $project->workspace->members()
            ->orderBy('name')
            ->get();

        return view('projects.settings', [
            'project' => $project,
            'workspaceMembers' => $workspaceMembers,
        ]);
    }

    /**
     * Update project info.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', Project::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Project::PRIORITIES)],
        ]);

        $oldStatus = $project->status;
        $project->update($validated);

        Activity::record($project->workspace, $request->user(), 'project_updated', [
            'project' => $project->name,
            'old_status' => $oldStatus,
            'new_status' => $project->status,
        ], $project);

        return back()->with('status', 'Đã cập nhật project.');
    }

    /**
     * Archive the project.
     */
    public function archive(Request $request, Project $project): RedirectResponse
    {
        $project->update(['status' => 'archived']);

        Activity::record($project->workspace, $request->user(), 'project_archived', [
            'project' => $project->name,
        ], $project);

        return redirect()
            ->route('workspaces.show', $project->workspace)
            ->with('status', 'Project "'.$project->name.'" đã được archive.');
    }

    /**
     * Restore an archived project.
     */
    public function restore(Request $request, Project $project): RedirectResponse
    {
        $project->update(['status' => 'active']);

        Activity::record($project->workspace, $request->user(), 'project_restored', [
            'project' => $project->name,
        ], $project);

        return back()->with('status', 'Project đã được khôi phục.');
    }

    /**
     * Delete the project permanently.
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $name = $project->name;
        $workspace = $project->workspace;

        $project->forceDelete();

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'Project "'.$name.'" đã bị xóa vĩnh viễn.');
    }
}
