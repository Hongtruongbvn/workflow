@extends('layouts.app')

@section('page_title', 'Profile Settings')

@section('content')
    <div class="grid cols-2">
        <div class="card">
            <h2>👤 Thông tin cá nhân</h2>

            <div class="profile-row">
                <img class="avatar-preview" src="{{ $user->avatarUrl() }}" alt="avatar">
                <div>
                    <div style="font-weight:600;">{{ $user->name }}</div>
                    <div class="text-muted text-sm">{{ $user->email }}</div>
                    <div class="text-sm {{ $user->hasVerifiedEmail() ? '' : 'validation-error' }}">
                        {{ $user->hasVerifiedEmail() ? '✅ Email đã xác minh' : '⚠ Chưa xác minh email' }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="field">
                    <label for="name">Họ và tên</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="validation-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="validation-error">{{ $message }}</div>@enderror
                    @if ($user->isDirty('email'))
                        <div class="text-sm text-muted">Lưu ý: đổi email sẽ yêu cầu xác minh lại.</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </form>
        </div>

        <div>
            <div class="card">
                <h2>🖼 Avatar</h2>

                @if (session('status') == 'avatar-updated')
                    <div class="alert success">Avatar đã được cập nhật.</div>
                @endif

                <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="field">
                        <label for="avatar">Chọn ảnh (JPG/PNG, tối đa 2MB)</label>
                        <input id="avatar" type="file" name="avatar" accept="image/*">
                        @error('avatar')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Cập nhật avatar</button>
                </form>
            </div>

            <div class="card mt-20">
                <h2>🔑 Đổi mật khẩu</h2>

                @if (session('status') == 'password-updated')
                    <div class="alert success">Mật khẩu đã được cập nhật.</div>
                @endif

                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PATCH')

                    <div class="field">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                        @error('current_password', 'updatePassword')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Mật khẩu mới</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password">
                        @error('password', 'updatePassword')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Xác nhận mật khẩu mới</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card mt-20">
        <h2>🔔 Notification Preferences</h2>
        <p class="text-muted text-sm" style="margin-bottom:14px;">Chọn loại thông báo bạn muốn nhận trong ứng dụng.</p>

        @if (session('status') == 'preferences-updated')
            <div class="alert success">Đã lưu tùy chọn thông báo.</div>
        @endif

        <form method="POST" action="{{ route('profile.preferences') }}">
            @csrf
            @method('PATCH')

            @php($settings = $user->settings)
            @php($prefs = ['notify_task_assigned' => ['Task assignment', 'Nhận thông báo khi có task được giao cho bạn'], 'notify_mention' => ['Mention', 'Nhận thông báo khi ai đó @mention bạn trong comment'], 'notify_deadline_reminder' => ['Deadline reminder', 'Nhắc trước khi task đến hạn và khi task quá hạn'], 'notify_project_updates' => ['Project updates', 'Nhận cập nhật chung về project']])

            @foreach ($prefs as $key => [$label, $desc])
                <div class="task-row" style="justify-content:flex-start; gap:10px;">
                    <input type="checkbox" id="{{ $key }}" name="{{ $key }}" value="1"
                           {{ ($settings?->{$key} ?? true) ? 'checked' : '' }}
                           style="width:auto; transform:scale(1.2);">
                    <div>
                        <label for="{{ $key }}" style="margin:0; font-weight:600;">☑ {{ $label }}</label>
                        <div class="text-muted text-sm">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary" style="margin-top:8px;">Lưu tùy chọn</button>
        </form>
    </div>

    <div class="card mt-20" style="border-color: #fecaca;">
        <h2 style="color: var(--danger);">🗑 Xóa tài khoản</h2>
        <p class="text-muted text-sm" style="margin-bottom:12px;">Hành động này không thể hoàn tác. Toàn bộ dữ liệu của bạn sẽ bị xóa vĩnh viễn.</p>

        <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa tài khoản? Hành động này không thể hoàn tác!')">
            @csrf
            @method('DELETE')

            <div class="field" style="max-width:320px;">
                <input type="password" name="password" placeholder="Nhập mật khẩu để xác nhận" required autocomplete="current-password">
                @error('password', 'userDeletion')<div class="validation-error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-danger">Xóa tài khoản vĩnh viễn</button>
        </form>
    </div>
@endsection
