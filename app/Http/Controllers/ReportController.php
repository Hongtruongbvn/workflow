<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Report for a single project.
     */
    public function project(Request $request, Project $project): View
    {
        $stats = $project->taskStats();

        // Per-member breakdown.
        $members = $project->members()->get()->map(function ($member) use ($project) {
            $base = $project->tasks()->where('assignee_id', $member->id);

            return [
                'user' => $member,
                'completed' => (clone $base)->where('status', 'done')->count(),
                'in_progress' => (clone $base)->whereIn('status', ['in_progress', 'review'])->count(),
                'todo' => (clone $base)->where('status', 'todo')->count(),
                'overdue' => (clone $base)->overdue()->count(),
            ];
        })->sortByDesc('completed')->values();

        // Tasks completed over the last 8 weeks (for a simple trend list).
        $timeline = collect(range(7, 0))->map(function ($weeksAgo) use ($project) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('d/m').' - '.$end->format('d/m'),
                'completed' => $project->tasks()
                    ->where('status', 'done')
                    ->whereBetween('completed_at', [$start, $end])
                    ->count(),
            ];
        })->reverse()->values();

        return view('reports.project', [
            'project' => $project,
            'stats' => $stats,
            'progress' => $project->progressPercent(),
            'members' => $members,
            'timeline' => $timeline,
            'milestones' => $project->milestones()->withCount(['tasks', 'tasks as tasks_done' => fn ($q) => $q->where('status', 'done')])->get(),
        ]);
    }

    /**
     * Workspace-level report: every project + member performance across projects.
     */
    public function workspace(Request $request, Workspace $workspace): View
    {
        $projects = $workspace->projects()
            ->withCount(['tasks', 'tasks as tasks_done' => fn ($q) => $q->where('status', 'done')])
            ->get()
            ->map(function ($project) {
                return [
                    'project' => $project,
                    'stats' => $project->taskStats(),
                    'progress' => $project->progressPercent(),
                ];
            });

        // Member performance across all workspace projects.
        $members = $workspace->members()->get()->map(function ($member) use ($workspace) {
            $base = Task::whereIn('project_id', $workspace->projects()->pluck('id'))
                ->where('assignee_id', $member->id);

            return [
                'user' => $member,
                'completed' => (clone $base)->where('status', 'done')->count(),
                'in_progress' => (clone $base)->whereIn('status', ['in_progress', 'review'])->count(),
                'todo' => (clone $base)->where('status', 'todo')->count(),
                'overdue' => (clone $base)->overdue()->count(),
            ];
        })->sortByDesc('completed')->values();

        return view('reports.workspace', [
            'workspace' => $workspace,
            'projects' => $projects,
            'members' => $members,
        ]);
    }
}
