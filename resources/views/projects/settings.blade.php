@extends('layouts.app')

@section('page_title', $project->name.' — Settings')

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">⚙️ Project Settings</h1>
        <div style="margin-left:auto;">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">← Project</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="grid cols-2">
        <div class="card">
            <h2>Thông tin Project</h2>

            <form method="POST" action="{{ route('projects.update', $project) }}">
                @csrf
                @method('PATCH')

                <div class="field">
                    <label for="name">Tên Project</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $project->name) }}" required>
                    @error('name')<div class="validation-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
                </div>

                <div class="grid cols-2">
                    <div class="field">
                        <label for="start_date">Ngày bắt đầu</label>
                        <input id="start_date" type="date" name="start_date"
                               value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="field">
                        <label for="due_date">Deadline</label>
                        <input id="due_date" type="date" name="due_date"
                               value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}">
                        @error('due_date')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            @foreach (\App\Models\Project::STATUSES as $s)
                                <option value="{{ $s }}" {{ old('status', $project->status) === $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="priority">Độ ưu tiên</label>
                        <select id="priority" name="priority">
                            @foreach (\App\Models\Project::PRIORITIES as $p)
                                <option value="{{ $p }}" {{ old('priority', $project->priority) === $p ? 'selected' : '' }}>
                                    {{ ucfirst($p) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </form>
        </div>

        <div>
            <div class="card">
                <h2>👥 Thành viên ({{ $project->members->count() }})</h2>

                @forelse ($project->members as $member)
                    <div class="task-row">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <img src="{{ $member->avatarUrl() }}" alt="" style="width:32px;height:32px;border-radius:50%;">
                            <div>
                                <div style="font-weight:500;">{{ $member->name }}</div>
                                <div class="text-muted text-sm">{{ $member->email }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}"
                              onsubmit="return confirm('Xóa {{ $member->name }} khỏi project?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding:6px 10px;">Xóa</button>
                        </form>
                    </div>
                @empty
                    <div class="empty">Chưa có thành viên nào</div>
                @endforelse

                <h2 style="margin-top:20px;">➕ Thêm thành viên từ workspace</h2>
                <form method="POST" action="{{ route('projects.members.store', $project) }}"
                      style="display:flex; gap:8px; align-items:flex-end;">
                    @csrf
                    <div class="field" style="flex:1; margin-bottom:0;">
                        <select name="user_id" required>
                            <option value="">— Chọn thành viên workspace —</option>
                            @foreach ($workspaceMembers as $wsMember)
                                @unless ($project->members->contains($wsMember))
                                    <option value="{{ $wsMember->id }}">{{ $wsMember->name }} ({{ $wsMember->email }})</option>
                                @endunless
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>
            </div>

            <div class="card mt-20">
                <h2>📦 Archive</h2>
                @if ($project->status === 'archived')
                    <form method="POST" action="{{ route('projects.restore', $project) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">↩ Khôi phục project</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('projects.archive', $project) }}"
                          onsubmit="return confirm('Archive project này?')">
                        @csrf
                        <button type="submit" class="btn btn-secondary">🗄 Archive project</button>
                    </form>
                @endif
            </div>

            <div class="card mt-20" style="border-color:#fecaca;">
                <h2 style="color:var(--danger);">🗑 Xóa Project</h2>
                <p class="text-muted text-sm" style="margin-bottom:12px;">Xóa vĩnh viễn project và toàn bộ tasks.</p>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('Xóa project {{ $project->name }} vĩnh viễn?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa vĩnh viễn</button>
                </form>
            </div>
        </div>
    </div>
@endsection
