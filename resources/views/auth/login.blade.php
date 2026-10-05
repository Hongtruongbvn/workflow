@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <div class="field" style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="remember" name="remember" style="width:auto;">
            <label for="remember" style="margin:0; font-weight:400;">Ghi nhớ đăng nhập</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
    </form>
@endsection

@section('links')
    <a href="{{ route('password.request') }}">Quên mật khẩu?</a><br>
    Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a>
@endsection
