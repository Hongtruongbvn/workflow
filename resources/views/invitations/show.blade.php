@extends('layouts.guest')

@section('title', 'Lời mời tham gia workspace')

@section('subtitle', 'Workspace Invitation')

@section('content')
    @if ($expired)
        <div class="alert error mb-0">
            ⚠ Lời mời này đã hết hạn hoặc đã được xử lý.
        </div>
    @else
        <div style="text-align:center; padding:10px 0;">
            <div style="font-size:40px; margin-bottom:8px;">🏢</div>
            <h2 style="font-size:20px; margin-bottom:4px;">{{ $invitation->workspace->name }}</h2>
            <p class="text-muted" style="margin-bottom:20px;">
                <strong>{{ $invitation->inviter->name }}</strong> đã mời bạn tham gia workspace này
                với quyền <strong>{{ $invitation->role }}</strong>.
            </p>

            @if (auth()->check() && strtolower(auth()->user()->email) === $invitation->email)
                <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block">✅ Accept Invitation</button>
                </form>
                <form method="POST" action="{{ route('invitations.decline', $invitation->token) }}" style="margin-top:10px;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-block">Decline</button>
                </form>
            @elseif (auth()->check())
                <div class="alert error">
                    Bạn đang đăng nhập bằng <strong>{{ auth()->user()->email }}</strong>, nhưng lời mời được gửi tới
                    <strong>{{ $invitation->email }}</strong>. Vui lòng đăng xuất và đăng nhập bằng đúng tài khoản.
                </div>
            @else
                <div class="alert info">
                    Để chấp nhận lời mời, bạn cần đăng nhập bằng tài khoản <strong>{{ $invitation->email }}</strong>
                    hoặc đăng ký tài khoản mới với email này.
                </div>
                <a href="{{ route('login') }}" class="btn btn-primary btn-block">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-secondary btn-block" style="margin-top:10px;">Đăng ký</a>
            @endif
        </div>
    @endif
@endsection

@section('links')
    @if (auth()->check())
        <a href="{{ route('dashboard') }}">← Về Dashboard</a>
    @else
        <span>WorkFlow Pro — Quản lý công việc & dự án</span>
    @endif
@endsection
