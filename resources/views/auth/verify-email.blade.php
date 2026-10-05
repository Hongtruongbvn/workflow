@extends('layouts.guest')

@section('title', 'Xác minh email')

@section('subtitle', 'Xác minh địa chỉ email của bạn')

@section('content')
    <div class="alert info mb-0">
        Cảm ơn bạn đã đăng ký! Trước khi bắt đầu, hãy xác minh địa chỉ email của bạn bằng cách
        nhấp vào link chúng tôi đã gửi tới <strong>{{ auth()->user()->email }}</strong>.
        Nếu bạn không nhận được email, chúng tôi có thể gửi lại cho bạn.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert success">
            Một link xác minh mới đã được gửi tới địa chỉ email của bạn.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-20">
        @csrf
        <button type="submit" class="btn btn-secondary btn-block">Gửi lại email xác minh</button>
    </form>
@endsection

@section('links')
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font-size:inherit;">
            Đăng xuất
        </button>
    </form>
@endsection
