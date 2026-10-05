<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTaskController extends Controller
{
    private const TABS = ['all', 'todo', 'in_progress', 'review', 'completed', 'overdue'];

    /**
     * Personal task list with tabs.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->tab, self::TABS) ? $request->tab : 'all';

        $query = $user->assignedTasks()
            ->with(['project.workspace', 'labels'])
            ->orderByRaw('due_date IS NULL, due_date ASC');

        $tasks = match ($tab) {
            'todo' => (clone $query)->where('status', 'todo')->get(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->get(),
            'review' => (clone $query)->where('status', 'review')->get(),
            'completed' => (clone $query)->where('status', 'done')->get(),
            'overdue' => (clone $query)->overdue()->get(),
            default => (clone $query)->get(),
        };

        // Counts for the tab badges.
        $base = $user->assignedTasks();
        $counts = [
            'all' => (clone $base)->count(),
            'todo' => (clone $base)->where('status', 'todo')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'review' => (clone $base)->where('status', 'review')->count(),
            'completed' => (clone $base)->where('status', 'done')->count(),
            'overdue' => (clone $base)->overdue()->count(),
        ];

        return view('tasks.my', [
            'tasks' => $tasks,
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }
}
