<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Monthly calendar of task deadlines.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $month = $request->filled('month')
            ? Carbon::parse($request->month.'-01')
            : Carbon::now()->startOfMonth();

        $tasks = $this->visibleTasks($user)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->with(['project', 'assignee'])
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn ($task) => $task->due_date->format('Y-m-d'));

        // Build a 6x7 grid starting on Monday.
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $days = collect(range(0, 41))->map(fn ($i) => $gridStart->copy()->addDays($i));

        return view('calendar.index', [
            'month' => $month,
            'days' => $days,
            'tasksByDay' => $tasks,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * Tasks in projects the user participates in + tasks assigned to them.
     */
    private function visibleTasks(User $user)
    {
        $projectIds = Project::query()
            ->whereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->orWhereHas('workspace.members', function ($q) use ($user) {
                $q->where('users.id', $user->id)->whereIn('workspace_members.role', ['owner', 'manager']);
            })
            ->pluck('id');

        return Task::query()
            ->where(function ($q) use ($projectIds, $user) {
                $q->whereIn('project_id', $projectIds)->orWhere('assignee_id', $user->id);
            });
    }
}
