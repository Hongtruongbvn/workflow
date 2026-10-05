@extends('layouts.app')

@section('page_title', 'Calendar')

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <h1 style="font-size:22px;">📅 {{ $month->translatedFormat('m/Y') }}</h1>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn-secondary">← Tháng trước</a>
            <a href="{{ route('calendar.index') }}" class="btn btn-secondary">Hôm nay</a>
            <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn-secondary">Tháng sau →</a>
        </div>
    </div>

    <div class="card" style="padding:14px;">
        <div class="calendar-grid">
            @foreach (['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'] as $dow)
                <div class="calendar-dow">{{ $dow }}</div>
            @endforeach

            @foreach ($days as $day)
                <div class="calendar-day
                            {{ $day->isCurrentMonth() ? '' : 'outside' }}
                            {{ $day->isToday() ? 'today' : '' }}">
                    <div class="calendar-day-num">{{ $day->day }}</div>

                    @isset($tasksByDay[$day->format('Y-m-d')])
                        @foreach ($tasksByDay[$day->format('Y-m-d')]->take(3) as $task)
                            <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}"
                               class="calendar-task {{ $task->isOverdue() ? 'overdue' : '' }} {{ $task->status === 'done' ? 'done' : '' }}"
                               title="{{ $task->title }} — {{ $task->project->name }}">
                                {{ Str::limit($task->title, 18) }}
                            </a>
                        @endforeach
                        @if ($tasksByDay[$day->format('Y-m-d')]->count() > 3)
                            <div class="calendar-more">+{{ $tasksByDay[$day->format('Y-m-d')]->count() - 3 }} nữa</div>
                        @endif
                    @endisset
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mt-20">
        <h2>📋 Tất cả deadline trong tháng ({{ $tasksByDay->flatten()->count() }})</h2>
        @forelse ($tasksByDay->sortKeys() as $date => $dayTasks)
            <div class="task-row">
                <span class="text-muted text-sm" style="width:90px; flex-shrink:0;">
                    <strong>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</strong>
                </span>
                <span style="flex:1;">
                    @foreach ($dayTasks as $task)
                        <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}" style="margin-right:12px;">
                            {{ $task->title }}
                        </a>
                    @endforeach
                </span>
                <span class="text-muted text-sm">{{ $dayTasks->first()->project->name }}</span>
            </div>
        @empty
            <div class="empty">Không có deadline nào trong tháng này</div>
        @endforelse
    </div>
@endsection
