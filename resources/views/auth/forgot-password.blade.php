@extends('layouts.guest')

@section('title', 'Quên mật khẩu')

@section('subtitle', 'Nhập email để nhận link đặt lại mật khẩu')

@section('content')
    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="validation-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi link đặt lại mật khẩu</button>
    </form>
@endsection

@section('links')
    <a href="{{ route('login') }}">← Quay lại đăng nhập</a>
@endsection
