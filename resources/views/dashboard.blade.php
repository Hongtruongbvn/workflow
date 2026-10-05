@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <h1 style="font-size:22px; margin-bottom:22px;">
        Xin chào, {{ auth()->user()->name }} 👋
    </h1>

    @if ($stats['overdue'] > 0)
        <div class="alert error">⚠ Bạn có <strong>{{ $stats['overdue'] }} task quá hạn</strong> cần xử lý!</div>
    @endif

    <div class="grid cols-4 mb-20">
        <div class="card stat">
            <span class="num">{{ $stats['total'] }}</span>
            <span class="label">Total Tasks</span>
        </div>
        <div class="card stat">
            <span class="num todo">{{ $stats['todo'] }}</span>
            <span class="label">To Do</span>
        </div>
        <div class="card stat">
            <span class="num progress">{{ $stats['in_progress'] }}</span>
            <span class="label">In Progress</span>
        </div>
        <div class="card stat">
            <span class="num done">{{ $stats['completed'] }}</span>
            <span class="label">Completed</span>
        </div>
    </div>

    <div class="grid cols-2">
        <div>
            <div class="card">
                <h2>⏳ Upcoming Deadlines</h2>
                @forelse ($upcoming as $task)
                    <div class="task-row">
                        <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}" class="title">
                            {{ $task->title }}
                        </a>
                        <span class="due {{ $task->isOverdue() ? 'overdue' : ($task->isDueToday() ? 'today' : '') }}">
                            {{ $task->dueLabel() }}
                        </span>
                    </div>
                @empty
                    <div class="empty">Không có task nào sắp đến hạn 🎉</div>
                @endforelse
            </div>

            @if ($overdueTasks->isNotEmpty())
                <div class="card mt-20" style="border-color:#fecaca;">
                    <h2 style="color:var(--danger);">⚠ Task quá hạn</h2>
                    @foreach ($overdueTasks as $task)
                        <div class="task-row">
                            <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}" class="title">
                                {{ $task->title }}
                            </a>
                            <span class="due overdue">{{ $task->due_date->format('d/m/Y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card mt-20">
                <h2>📁 Projects đang tham gia ({{ $projects->count() }})</h2>
                @forelse ($projects as $item)
                    <div class="task-row">
                        <div style="flex:1;">
                            <a href="{{ route('projects.show', $item['model']) }}" class="title">
                                {{ $item['model']->name }}
                            </a>
                            <span class="text-muted text-sm"> · {{ $item['model']->workspace->name }}</span>
                            <div class="progress-track" style="height:6px; margin-top:5px; max-width:280px;">
                                <div class="progress-fill" style="width: {{ $item['progress'] }}%;"></div>
                            </div>
                        </div>
                        <span class="text-muted text-sm">{{ $item['progress'] }}%</span>
                    </div>
                @empty
                    <div class="empty">Chưa tham gia project nào</div>
                @endforelse
            </div>
        </div>

        <div>
            <div class="card">
                <h2>📋 My Tasks</h2>
                @php($myRecent = auth()->user()->assignedTasks()->with('project')->where('status', '!=', 'done')->latest()->take(6)->get())
                @forelse ($myRecent as $task)
                    <div class="task-row">
                        <span>
                            <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}" class="title">{{ $task->title }}</a>
                            <span class="badge-pill status-{{ $task->status }}" style="margin-left:6px;">{{ str_replace('_', ' ', $task->status) }}</span>
                        </span>
                        <span class="text-muted text-sm">{{ $task->project->name }}</span>
                    </div>
                @empty
                    <div class="empty">Chưa có task nào được giao</div>
                @endforelse
            </div>

            <div class="card mt-20">
                <h2>🕐 Hoạt động gần đây</h2>
                @forelse ($activities as $activity)
                    <div class="task-row">
                        <span>
                            <img src="{{ $activity->user->avatarUrl() }}" alt="" style="width:22px;height:22px;border-radius:50%;vertical-align:middle;margin-right:6px;">
                            <strong>{{ $activity->user->name }}</strong>
                            <span class="text-muted text-sm">— {{ $activity->type }}
                                @if (isset($activity->data['old'])) ({{ $activity->data['old'] }} → {{ $activity->data['new'] }}) @endif
                            </span>
                        </span>
                        <span class="text-muted text-sm">{{ $activity->created_at->format('H:i d/m') }}</span>
                    </div>
                @empty
                    <div class="empty">Chưa có hoạt động nào</div>
                @endforelse
            </div>

            @if ($recentlyViewed->isNotEmpty())
                <div class="card mt-20">
                    <h2>👁 Recently Viewed</h2>
                    @foreach ($recentlyViewed as $item)
                        <div class="task-row">
                            <span>
                                {{ $item->typeLabel() }}
                                @if ($item->viewable instanceof \App\Models\Project)
                                    <a href="{{ route('projects.show', $item->viewable_id) }}">{{ $item->viewable->name }}</a>
                                @else
                                    <a href="{{ route('tasks.show', [$item->viewable->project_id, $item->viewable_id]) }}">{{ $item->viewable->title }}</a>
                                @endif
                            </span>
                            <span class="text-muted text-sm">{{ $item->viewed_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-20">
        <h2 style="display:flex; align-items:center; justify-content:space-between;">
            <span>🏢 My Workspaces</span>
            <a href="{{ route('workspaces.create') }}" class="btn btn-primary" style="padding:7px 14px;">➕ New Workspace</a>
        </h2>
        <div class="grid cols-4">
            @forelse ($workspaces as $workspace)
                <a href="{{ route('workspaces.show', $workspace) }}" class="ws-card" style="color:inherit;">
                    <span class="name">{{ $workspace->name }}</span>
                    <span class="meta">{{ $workspace->members_count }} Members · {{ $workspace->projects_count }} Projects</span>
                </a>
            @empty
                <div class="empty" style="grid-column:1/-1;">Bạn chưa tham gia workspace nào. Tạo workspace đầu tiên để bắt đầu!</div>
            @endforelse
        </div>
    </div>
@endsection
