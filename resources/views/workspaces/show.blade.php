@extends('layouts.app')

@section('page_title', $workspace->name)

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        @if ($workspace->logo_path)
            <img src="{{ asset('storage/'.$workspace->logo_path) }}" alt="logo"
                 style="width:52px;height:52px;border-radius:12px;object-fit:cover;border:1px solid var(--border);">
        @else
            <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,var(--primary),#a855f7);
                        display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;">
                {{ strtoupper(substr($workspace->name, 0, 1)) }}
            </div>
        @endif
        <div>
            <h1 style="font-size:22px;">{{ $workspace->name }}</h1>
            <span class="text-muted text-sm">
                {{ $workspace->description }}
                · Vai trò của bạn: <strong>{{ $stats['my_role'] }}</strong>
            </span>
        </div>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a href="{{ route('workspaces.members', $workspace) }}" class="btn btn-secondary">👥 Members</a>
            @if (auth()->user()->canManageWorkspace($workspace))
                <a href="{{ route('workspaces.edit', $workspace) }}" class="btn btn-secondary">⚙️ Settings</a>
                <a href="{{ route('projects.create', $workspace) }}" class="btn btn-primary">➕ New Project</a>
            @endif
        </div>
    </div>

    <div class="grid cols-4 mb-20">
        <div class="card stat">
            <span class="num">{{ $stats['members'] }}</span>
            <span class="label">Members</span>
        </div>
        <div class="card stat">
            <span class="num progress">{{ $stats['projects'] }}</span>
            <span class="label">Active Projects</span>
        </div>
        <div class="card stat">
            <span class="num todo">{{ $stats['archived'] }}</span>
            <span class="label">Archived</span>
        </div>
        <div class="card stat">
            <span class="num">{{ $recentTasks->count() }}</span>
            <span class="label">Tasks gần đây</span>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <h2>📁 Projects</h2>
            @forelse ($projects as $project)
                <div class="task-row">
                    <span>
                        <a href="{{ route('projects.show', $project) }}" class="title">● {{ $project->name }}</a>
                        <span class="badge-pill status-{{ str_replace(' ', '_', $project->status) }}"
                              style="margin-left:8px;">{{ $project->status }}</span>
                    </span>
                    <span class="text-muted text-sm">
                        {{ $project->members_count }} members · {{ $project->tasks_count }} tasks
                    </span>
                </div>
            @empty
                <div class="empty">Chưa có project nào.</div>
            @endforelse
        </div>

        <div class="card">
            <h2>🕐 Hoạt động gần đây</h2>
            @forelse ($activities as $activity)
                <div class="task-row">
                    <span>
                        <strong>{{ $activity->user->name }}</strong>
                        <span class="text-muted text-sm">— {{ $activity->type }}</span>
                    </span>
                    <span class="text-muted text-sm">{{ $activity->created_at->format('H:i d/m') }}</span>
                </div>
            @empty
                <div class="empty">Chưa có hoạt động nào</div>
            @endforelse
        </div>
    </div>
@endsection
