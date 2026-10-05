@extends('layouts.app')

@section('page_title', $project->name)

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:6px;">
        <span class="text-muted text-sm">
            <a href="{{ route('workspaces.show', $project->workspace) }}">{{ $project->workspace->name }}</a> /
        </span>
    </div>
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">{{ $project->name }}</h1>
        <span class="badge-pill status-{{ str_replace(' ', '_', $project->status) }}">{{ $project->status }}</span>
        <span class="badge-pill {{ $project->priority }}">{{ $project->priority }}</span>
        <form method="POST" action="{{ route('favorites.toggle', $project) }}" style="margin-left:2px;">
            @csrf
            <button type="submit" class="star-btn" title="Yêu thích">{{ $isFavorite ? '⭐' : '☆' }}</button>
        </form>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a href="{{ route('projects.board', $project) }}" class="btn btn-primary">📋 Kanban Board</a>
            @if (auth()->user()->canManageWorkspace($project->workspace))
                <a href="{{ route('projects.report', $project) }}" class="btn btn-secondary">📊 Report</a>
                <a href="{{ route('projects.settings', $project) }}" class="btn btn-secondary">⚙️ Settings</a>
            @endif
        </div>
    </div>

    <div class="card mb-20">
        <h2 style="display:flex; justify-content:space-between;">
            <span>Progress</span>
            <span>{{ $progress }}%</span>
        </h2>
        <div class="progress-track">
            <div class="progress-fill" style="width: {{ $progress }}%;"></div>
        </div>
        <div class="text-muted text-sm" style="margin-top:6px; display:flex; justify-content:space-between;">
            <span>Bắt đầu: {{ $project->start_date?->format('d/m/Y') ?? '—' }}</span>
            <span>Deadline: <strong class="{{ $project->due_date && $project->due_date->isPast() ? 'overdue-text' : '' }}">
                {{ $project->due_date?->format('d/m/Y') ?? '—' }}</strong></span>
        </div>
    </div>

    <div class="grid cols-4 mb-20">
        <div class="card stat">
            <span class="num">{{ $stats['total'] }}</span>
            <span class="label">Total Tasks</span>
        </div>
        <div class="card stat">
            <span class="num todo">{{ $stats['todo'] }}</span>
            <span class="label">Todo</span>
        </div>
        <div class="card stat">
            <span class="num progress">{{ $stats['in_progress'] + $stats['review'] }}</span>
            <span class="label">In Progress / Review</span>
        </div>
        <div class="card stat">
            <span class="num done">{{ $stats['done'] }}</span>
            <span class="label">Completed</span>
        </div>
    </div>

    @if ($stats['overdue'] > 0)
        <div class="alert error">⚠ {{ $stats['overdue'] }} task(s) đang quá hạn!</div>
    @endif

    <div class="grid cols-2">
        <div class="card">
            <h2>✅ Task hoàn thành gần đây</h2>
            @forelse ($recentCompleted as $task)
                <div class="task-row">
                    <span class="title">{{ $task->title }}</span>
                    <span class="due completed">{{ $task->completed_at?->format('d/m H:i') }}</span>
                </div>
            @empty
                <div class="empty">Chưa có task nào hoàn thành</div>
            @endforelse
        </div>

        <div class="card">
            <h2>⚠ Task quá hạn</h2>
            @forelse ($overdue as $task)
                <div class="task-row">
                    <span class="title">{{ $task->title }}</span>
                    <span class="due overdue">{{ $task->due_date->format('d/m/Y') }}</span>
                </div>
            @empty
                <div class="empty">Không có task quá hạn 🎉</div>
            @endforelse
        </div>
    </div>

    <div class="card mt-20">
        <h2 style="display:flex; justify-content:space-between;">
            <span>🎯 Milestones ({{ $milestones->count() }})</span>
        </h2>

        @forelse ($milestones as $milestone)
            @php($mp = $milestone->tasks_count > 0 ? (int) round($milestone->tasks_done / $milestone->tasks_count * 100) : 0)
            <div class="milestone-row">
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <strong>{{ $milestone->name }}</strong>
                        @if ($milestone->completed_at)
                            <span class="badge-pill status-done">✓ Completed</span>
                        @elseif ($milestone->due_date && $milestone->due_date->isPast())
                            <span class="badge-pill urgent">⚠ Overdue</span>
                        @endif
                    </div>
                    <div class="progress-track" style="margin-top:6px; max-width:420px;">
                        <div class="progress-fill" style="width: {{ $mp }}%;"></div>
                    </div>
                    <div class="text-muted text-sm" style="margin-top:4px;">
                        {{ $milestone->tasks_done }}/{{ $milestone->tasks_count }} tasks · {{ $mp }}%
                        @if ($milestone->due_date) · deadline {{ $milestone->due_date->format('d/m/Y') }} @endif
                    </div>
                </div>
                @if (auth()->user()->canManageWorkspace($project->workspace))
                    <form method="POST" action="{{ route('milestones.destroy', [$project, $milestone]) }}"
                          onsubmit="return confirm('Xoa milestone nay? (Task duoc giu lai)')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="padding:5px 10px;">✕</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="empty">Chưa có milestone nào</div>
        @endforelse

        @if (auth()->user()->canManageWorkspace($project->workspace))
            <form method="POST" action="{{ route('milestones.store', $project) }}"
                  style="display:flex; gap:8px; align-items:flex-end; margin-top:14px; padding-top:14px; border-top:1px solid var(--border);">
                @csrf
                <div style="flex:2;">
                    <input type="text" name="name" placeholder="Tên milestone... VD: Authentication" required>
                </div>
                <div style="flex:1;">
                    <input type="date" name="due_date">
                </div>
                <button type="submit" class="btn btn-primary">➕ Thêm milestone</button>
            </form>
        @endif
    </div>

    <div class="card mt-20">
        <h2>👥 Members ({{ $project->members->count() }})</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @forelse ($project->members as $member)
                <div style="display:flex; align-items:center; gap:6px; padding:6px 12px 6px 6px; border:1px solid var(--border); border-radius:999px;">
                    <img src="{{ $member->avatarUrl() }}" alt="" style="width:26px;height:26px;border-radius:50%;">
                    {{ $member->name }}
                </div>
            @empty
                <div class="empty">Chưa có thành viên</div>
            @endforelse
        </div>
    </div>
@endsection
