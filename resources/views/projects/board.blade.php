@extends('layouts.app')

@section('page_title', $project->name.' — Kanban')

@section('content')
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
        <span class="text-muted text-sm">
            <a href="{{ route('workspaces.show', $project->workspace) }}">{{ $project->workspace->name }}</a> /
            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a> /
        </span>
    </div>
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
        <h1 style="font-size:22px;">📋 Kanban Board</h1>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>

    {{-- Filter & search bar --}}
    <form method="GET" action="{{ route('projects.board', $project) }}" class="card filter-bar">
        <input type="text" name="q" placeholder="🔍 Tim task..." value="{{ $filters['q'] ?? '' }}" style="flex:1; min-width:140px;">

        <select name="priority">
            <option value="">Priority: tất cả</option>
            @foreach (\App\Models\Task::PRIORITIES as $p)
                <option value="{{ $p }}" {{ ($filters['priority'] ?? '') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>

        <select name="assignee">
            <option value="">Assignee: tất cả</option>
            <option value="me" {{ ($filters['assignee'] ?? '') === 'me' ? 'selected' : '' }}>Của tôi</option>
            <option value="unassigned" {{ ($filters['assignee'] ?? '') === 'unassigned' ? 'selected' : '' }}>Chưa giao</option>
            @foreach ($projectMembers as $member)
                <option value="{{ $member->id }}" {{ ($filters['assignee'] ?? '') == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
            @endforeach
        </select>

        <select name="label">
            <option value="">Label: tất cả</option>
            @foreach ($workspaceLabels as $label)
                <option value="{{ $label->id }}" {{ ($filters['label'] ?? '') == $label->id ? 'selected' : '' }}>{{ $label->name }}</option>
            @endforeach
        </select>

        <select name="due">
            <option value="">Deadline: tất cả</option>
            <option value="overdue" {{ ($filters['due'] ?? '') === 'overdue' ? 'selected' : '' }}>⚠ Quá hạn</option>
            <option value="today" {{ ($filters['due'] ?? '') === 'today' ? 'selected' : '' }}>Hôm nay</option>
            <option value="week" {{ ($filters['due'] ?? '') === 'week' ? 'selected' : '' }}>7 ngày tới</option>
            <option value="none" {{ ($filters['due'] ?? '') === 'none' ? 'selected' : '' }}>Chưa có deadline</option>
        </select>

        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('projects.board', $project) }}" class="btn btn-secondary">Xóa</a>
    </form>

    <div class="kanban" id="kanban" data-move-url="{{ url('projects/'.$project->id.'/tasks') }}">
        @foreach ($columns as $column)
            <div class="kanban-col" data-status="{{ $column }}">
                <div class="kanban-col-header">
                    <span class="kanban-col-title kanban-dot dot-{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                    <span class="kanban-count">{{ ($tasksByStatus[$column] ?? collect())->count() }}</span>
                </div>

                <div class="kanban-cards" data-column="{{ $column }}">
                    @forelse ($tasksByStatus[$column] ?? [] as $task)
                        @include('partials.task-card')
                    @empty
                        <div class="kanban-empty">Kéo task vào đây</div>
                    @endforelse
                </div>

                @if (auth()->user()->canManageWorkspace($project->workspace))
                    <button class="kanban-add" data-toggle-column="{{ $column }}">+ Thêm task</button>
                @endif
            </div>
        @endforeach
    </div>

    @if (auth()->user()->canManageWorkspace($project->workspace))
        <div class="card mt-20" id="task-create-form" style="display:none; max-width:720px;">
            <h2 id="task-create-title">➕ Thêm Task</h2>

            <form method="POST" action="{{ route('tasks.store', $project) }}">
                @csrf

                <input type="hidden" name="status" id="create-status" value="todo">

                <div class="field">
                    <label for="title">Task *</label>
                    <input id="title" type="text" name="title" required placeholder="VD: Implement Login">
                    @error('title')<div class="validation-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="task-description">Mô tả</label>
                    <textarea id="task-description" name="description" rows="2"></textarea>
                </div>

                <div class="grid cols-3">
                    <div class="field">
                        <label for="task-priority">Priority</label>
                        <select id="task-priority" name="priority">
                            @foreach (\App\Models\Task::PRIORITIES as $p)
                                <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="assignee_id">Assignee</label>
                        <select id="assignee_id" name="assignee_id">
                            <option value="">— Chưa giao —</option>
                            @foreach ($project->members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="task-due">Due Date</label>
                        <input id="task-due" type="date" name="due_date">
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary">Tạo Task</button>
                    <button type="button" class="btn btn-secondary" id="task-create-cancel">Hủy</button>
                </div>
            </form>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/kanban.js') }}"></script>
@endpush
