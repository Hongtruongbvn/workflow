@extends('layouts.app')

@section('page_title', 'Lời mời của tôi')

@section('content')
    <div style="display:flex; align-items:center; margin-bottom:22px;">
        <h1 style="font-size:22px;">✉️ Lời mời tham gia workspace</h1>
    </div>

    <div class="card" style="max-width:640px;">
        @forelse ($invitations as $invitation)
            <div class="task-row">
                <div>
                    <div style="font-weight:600;">{{ $invitation->workspace->name }}</div>
                    <div class="text-muted text-sm">
                        {{ $invitation->inviter->name }} mời bạn với quyền {{ $invitation->role }}
                        · hết hạn {{ $invitation->expires_at->format('d/m/Y') }}
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="{{ route('invitations.show', $invitation->token) }}" class="btn btn-primary"
                       style="padding:7px 14px;">Xem</a>
                </div>
            </div>
        @empty
            <div class="empty">Bạn không có lời mời nào ✌️</div>
        @endforelse
    </div>
@endsection
