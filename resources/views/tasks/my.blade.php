@extends('layouts.app')

@section('page_title', 'My Tasks')

@section('content')
    <h1 style="font-size:22px; margin-bottom:20px;">📋 My Tasks</h1>

    <div class="tabs">
        @foreach (['all' => 'Tất cả', 'todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed', 'overdue' => '⚠ Overdue'] as $key => $label)
            <a href="{{ route('tasks.my', ['tab' => $key]) }}"
               class="tab {{ $tab === $key ? 'active' : '' }}">
                {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        @forelse ($tasks as $task)
            <div class="task-row">
                <div style="flex:1;">
                    <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}" class="title">
                        {{ $task->title }}
                    </a>
                    <span class="badge-pill {{ $task->priority }}" style="margin-left:8px;">{{ $task->priority }}</span>
                    <span class="badge-pill status-{{ $task->status }}" style="margin-left:4px;">
                        {{ str_replace('_', ' ', $task->status) }}
                    </span>
                </div>
                <span class="text-muted text-sm">{{ $task->project->name }}</span>
                <span class="due {{ $task->isOverdue() ? 'overdue' : ($task->isDueToday() ? 'today' : ($task->status === 'done' ? 'completed' : '')) }}">
                    {{ $task->dueLabel() }}
                </span>
            </div>
        @empty
            <div class="empty">Không có task nào trong tab này 🎉</div>
        @endforelse
    </div>
@endsection
