@extends('layouts.guest')

@section('title', 'Đăng ký')

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label for="name">Họ và tên</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Xác nhận mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
    </form>
@endsection

@section('links')
    Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a>
@endsection
