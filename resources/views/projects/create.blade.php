@extends('layouts.app')

@section('page_title', 'Tạo Project')

@section('content')
    <div class="card" style="max-width:680px;">
        <h2>📁 Tạo Project trong "{{ $workspace->name }}"</h2>

        <form method="POST" action="{{ route('projects.store', $workspace) }}">
            @csrf

            <div class="field">
                <label for="name">Tên Project *</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                       placeholder="VD: E-commerce Website">
                @error('name')<div class="validation-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="grid cols-2">
                <div class="field">
                    <label for="start_date">Ngày bắt đầu</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}">
                    @error('start_date')<div class="validation-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="due_date">Deadline</label>
                    <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}">
                    @error('due_date')<div class="validation-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        @foreach (\App\Models\Project::STATUSES as $s)
                            <option value="{{ $s }}" {{ old('status', 'planning') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="priority">Độ ưu tiên</label>
                    <select id="priority" name="priority">
                        @foreach (\App\Models\Project::PRIORITIES as $p)
                            <option value="{{ $p }}" {{ old('priority', 'medium') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label>Thêm thành viên (từ workspace)</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; padding:10px; border:1px solid var(--border); border-radius:8px;">
                    @foreach ($workspace->members as $member)
                        <label style="display:flex; align-items:center; gap:6px; font-size:13.5px; font-weight:400;">
                            <input type="checkbox" name="members[]" value="{{ $member->id }}"
                                   {{ $member->id === auth()->id() ? 'checked disabled' : '' }}
                                   style="width:auto;">
                            {{ $member->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Tạo Project</button>
                <a href="{{ route('workspaces.show', $workspace) }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection
