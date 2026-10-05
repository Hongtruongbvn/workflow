@extends('layouts.app')

@section('page_title', $workspace->name.' — Settings')

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">⚙️ Workspace Settings</h1>
        <div style="margin-left:auto;">
            <a href="{{ route('workspaces.show', $workspace) }}" class="btn btn-secondary">← Workspace</a>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <h2>Thông tin Workspace</h2>

            <form method="POST" action="{{ route('workspaces.update', $workspace) }}">
                @csrf
                @method('PATCH')

                <div class="field">
                    <label for="name">Tên Workspace</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $workspace->name) }}" required>
                    @error('name')<div class="validation-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="3">{{ old('description', $workspace->description) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </form>
        </div>

        <div>
            <div class="card">
                <h2>Logo</h2>

                @if (session('status'))
                    <div class="alert success">{{ session('status') }}</div>
                @endif

                <div class="profile-row">
                    @if ($workspace->logo_path)
                        <img src="{{ asset('storage/'.$workspace->logo_path) }}" alt="logo" class="avatar-preview"
                             style="border-radius:12px;">
                    @else
                        <div class="avatar-preview" style="border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            {{ strtoupper(substr($workspace->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('workspaces.logo', $workspace) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="field">
                        <input type="file" name="logo" accept="image/*">
                        @error('logo')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Cập nhật logo</button>
                </form>
            </div>

            <div class="card mt-20" style="border-color:#fecaca;">
                <h2 style="color:var(--danger);">🗑 Xóa Workspace</h2>
                <p class="text-muted text-sm" style="margin-bottom:12px;">
                    Xóa workspace sẽ xóa toàn bộ projects, tasks và dữ liệu liên quan. Không thể hoàn tác!
                </p>

                <form method="POST" action="{{ route('workspaces.destroy', $workspace) }}"
                      onsubmit="return confirm('Xóa workspace {{ $workspace->name }}? Toàn bộ dữ liệu sẽ bị xóa vĩnh viễn!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa Workspace vĩnh viễn</button>
                </form>
            </div>
        </div>
    </div>
@endsection
