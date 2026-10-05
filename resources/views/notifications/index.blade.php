@extends('layouts.app')

@section('page_title', 'Notifications')

@section('content')
    <div style="display:flex; align-items:center; margin-bottom:20px;">
        <h1 style="font-size:22px;">🔔 Notifications</h1>
        <div style="margin-left:auto;">
            @if ($unread->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Chưa đọc ({{ $unread->count() }})</h2>
        @forelse ($unread as $notification)
            <div class="notif-row unread">
                <span class="notif-icon">{{ match ($notification->data['type'] ?? '') {
                    'task_assigned' => '📌',
                    'mention' => '💬',
                    'deadline_overdue' => '⚠',
                    'deadline_due_tomorrow' => '⏰',
                    'workspace_invited' => '✉️',
                    'invitation_accepted' => '🎉',
                    'invitation_declined' => '😕',
                    default => '🔔',
                } }}</span>
                <div style="flex:1;">
                    <div style="font-weight:600;">{{ $notification->data['title'] ?? '' }}</div>
                    <div class="text-sm" style="color:var(--text-muted);">{{ $notification->data['message'] ?? '' }}</div>
                    <div class="text-muted text-sm">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
                @if (! empty($notification->data['link']))
                    <a href="{{ $notification->data['link'] }}" class="btn btn-primary" style="padding:6px 12px;">Xem</a>
                @endif
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding:6px 12px;">✓</button>
                </form>
            </div>
        @empty
            <div class="empty">Không có thông báo chưa đọc ✨</div>
        @endforelse
    </div>

    @if ($read->isNotEmpty())
        <div class="card mt-20">
            <h2>Đã đọc</h2>
            @foreach ($read as $notification)
                <div class="notif-row">
                    <span class="notif-icon">{{ match ($notification->data['type'] ?? '') {
                        'task_assigned' => '📌',
                        'mention' => '💬',
                        'deadline_overdue' => '⚠',
                        'deadline_due_tomorrow' => '⏰',
                        'workspace_invited' => '✉️',
                        'invitation_accepted' => '🎉',
                        'invitation_declined' => '😕',
                        default => '🔔',
                    } }}</span>
                    <div style="flex:1;">
                        <div>{{ $notification->data['title'] ?? '' }}</div>
                        <div class="text-sm" style="color:var(--text-muted);">{{ $notification->data['message'] ?? '' }}</div>
                        <div class="text-muted text-sm">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    @if (! empty($notification->data['link']))
                        <a href="{{ $notification->data['link'] }}" class="btn btn-secondary" style="padding:6px 12px;">Xem</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
