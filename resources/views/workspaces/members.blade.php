@extends('layouts.app')

@section('page_title', $workspace->name.' — Members')

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">👥 Members — {{ $workspace->name }}</h1>
        <div style="margin-left:auto;">
            <a href="{{ route('workspaces.show', $workspace) }}" class="btn btn-secondary">← Workspace</a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="grid cols-2">
        <div class="card">
            <h2>Thành viên ({{ $members->count() }})</h2>
            @forelse ($members as $member)
                <div class="task-row" style="gap:10px;">
                    <img src="{{ $member->avatarUrl() }}" alt="" style="width:36px;height:36px;border-radius:50%;">
                    <div style="flex:1;">
                        <div style="font-weight:600;">
                            {{ $member->name }}
                            @if ($member->pivot->role === 'owner')
                                <span class="badge-pill urgent">Owner</span>
                            @elseif ($member->pivot->role === 'manager')
                                <span class="badge-pill high">Manager</span>
                            @else
                                <span class="badge-pill status-todo">Member</span>
                            @endif
                        </div>
                        <div class="text-muted text-sm">
                            {{ $member->email }} · {{ $memberProjectCounts[$member->id] ?? 0 }} projects
                        </div>
                    </div>

                    @if (auth()->user()->isOwnerOf($workspace) && $member->pivot->role !== 'owner')
                        <form method="POST" action="{{ route('workspaces.members.update', [$workspace, $member]) }}"
                              style="display:flex; gap:6px; align-items:center;"
                              onsubmit="return confirm('Đổi quyền của {{ $member->name }}?')">
                            @csrf
                            @method('PATCH')
                            <select name="role" style="width:auto; padding:6px 8px;">
                                <option value="manager" {{ $member->pivot->role === 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" style="padding:6px 10px;">Lưu</button>
                        </form>
                        <form method="POST" action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}"
                              onsubmit="return confirm('Xóa {{ $member->name }} khỏi workspace?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:6px 10px;">Xóa</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="empty">Chưa có thành viên nào</div>
            @endforelse
        </div>

        <div>
            @if (auth()->user()->canManageWorkspace($workspace))
                <div class="card">
                    <h2>✉️ Mời thành viên</h2>
                    @if (session('status'))
                        <div class="alert success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('workspaces.invitations.store', $workspace) }}">
                        @csrf

                        <div class="field">
                            <label for="email">Email người được mời *</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                   placeholder="minh@gmail.com">
                            @error('email')<div class="validation-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="field">
                            <label for="role">Quyền</label>
                            <select id="role" name="role">
                                <option value="member">Member</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Gửi lời mời</button>
                    </form>
                </div>

                <div class="card mt-20">
                    <h2>⏳ Lời mời đang chờ ({{ $invitations->count() }})</h2>
                    @forelse ($invitations as $invitation)
                        <div class="task-row">
                            <div>
                                <div style="font-weight:500;">{{ $invitation->email }}</div>
                                <div class="text-muted text-sm">
                                    {{ $invitation->role }} · mời bởi {{ $invitation->inviter->name }}
                                    · hết hạn {{ $invitation->expires_at->format('d/m') }}
                                </div>
                            </div>
                            @if (auth()->user()->isOwnerOf($workspace))
                                <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px;">Thu hồi</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="empty">Không có lời mời nào đang chờ</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
@endsection
