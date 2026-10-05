@extends('layouts.guest')

@section('title', 'Đặt lại mật khẩu')

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ $request->email ?? old('email') }}" required autofocus>
            @error('email')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password">Mật khẩu mới</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Xác nhận mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Đặt lại mật khẩu</button>
    </form>
@endsection

@section('links')
    <a href="{{ route('login') }}">← Quay lại đăng nhập</a>
@endsection
