@extends('layouts.app')

@section('page_title', 'Tạo Workspace')

@section('content')
    <div class="card" style="max-width:640px;">
        <h2>🏢 Tạo Workspace mới</h2>
        <p class="text-muted text-sm" style="margin-bottom:16px;">
            Workspace là không gian làm việc của nhóm. Bạn sẽ là <strong>Owner</strong> của workspace này.
        </p>

        <form method="POST" action="{{ route('workspaces.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label for="name">Tên Workspace *</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                       placeholder="VD: Hong Development">
                @error('name')<div class="validation-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Software development team">{{ old('description') }}</textarea>
                @error('description')<div class="validation-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="logo">Logo (tùy chọn)</label>
                <input id="logo" type="file" name="logo" accept="image/*">
                @error('logo')<div class="validation-error">{{ $message }}</div>@enderror
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Tạo Workspace</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection
