<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the user's personal dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $myTasks = $user->assignedTasks()->with('project')->get();

        $stats = [
            'total' => $myTasks->count(),
            'todo' => $myTasks->where('status', 'todo')->count(),
            'in_progress' => $myTasks->whereIn('status', ['in_progress', 'review'])->count(),
            'completed' => $myTasks->where('status', 'done')->count(),
            'overdue' => $myTasks->filter(fn ($task) => $task->isOverdue())->count(),
        ];

        $upcoming = $myTasks
            ->filter(fn ($task) => $task->status !== 'done' && $task->due_date)
            ->sortBy('due_date')
            ->take(6);

        $overdueTasks = $myTasks->filter(fn ($task) => $task->isOverdue())->take(5);

        // Projects the user participates in (+ manager-only workspaces).
        $projects = Project::query()
            ->where(function ($q) use ($user) {
                $q->whereHas('members', fn ($m) => $m->where('users.id', $user->id))
                    ->orWhereHas('workspace.members', function ($m) use ($user) {
                        $m->where('users.id', $user->id)->whereIn('workspace_members.role', ['owner', 'manager']);
                    });
            })
            ->where('status', '!=', 'archived')
            ->with('workspace')
            ->get()
            ->map(fn ($project) => [
                'model' => $project,
                'progress' => $project->progressPercent(),
                'stats' => $project->taskStats(),
            ]);

        // Latest activity across the user's workspaces.
        $workspaceIds = $user->workspaceMemberships()->pluck('workspace_id');
        $activities = Activity::whereIn('workspace_id', $workspaceIds)
            ->with(['user', 'project'])
            ->latest()
            ->take(8)
            ->get();

        $recentlyViewed = $user->recentlyViewed()->with('viewable')->take(6)->get();

        return view('dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'overdueTasks' => $overdueTasks,
            'projects' => $projects,
            'activities' => $activities,
            'recentlyViewed' => $recentlyViewed,
            'workspaces' => $user->workspaces()->withCount('members', 'projects')->get(),
        ]);
    }
}
