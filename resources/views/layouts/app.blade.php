<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WorkFlow Pro')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="logo">⚡</span>
            <span>WorkFlow Pro</span>
        </div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            🏠 Dashboard
        </a>

        <div class="nav-label">Workspace</div>
        @foreach(auth()->user()->workspaces()->withPivot('role')->get() as $sidebarWorkspace)
            <a href="{{ route('workspaces.show', $sidebarWorkspace) }}"
               class="nav-item {{ isset($currentWorkspace) && $currentWorkspace->is($sidebarWorkspace) ? 'active' : '' }}"
               title="{{ $sidebarWorkspace->name }}">
                @if ($sidebarWorkspace->logo_path)
                    <img src="{{ asset('storage/'.$sidebarWorkspace->logo_path) }}" alt=""
                         style="width:18px;height:18px;border-radius:4px;object-fit:cover;">
                @else
                    🏢
                @endif
                {{ Str::limit($sidebarWorkspace->name, 18) }}
            </a>
        @endforeach
        <a href="{{ route('workspaces.create') }}" class="nav-item">➕ New Workspace</a>

        <div class="nav-label">Work</div>
        <a href="{{ route('tasks.my') }}" class="nav-item {{ request()->routeIs('tasks.my') ? 'active' : '' }}">📋 My Tasks</a>
        <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.index') ? 'active' : '' }}">📅 Calendar</a>
        <a href="{{ route('trash.index') }}" class="nav-item {{ request()->routeIs('trash.index') ? 'active' : '' }}">🗑 Trash</a>
        @if (auth()->user()->workspaceMemberships()->whereIn('role', ['owner', 'manager'])->exists())
            <a href="{{ route('workspaces.reports', auth()->user()->workspaceMemberships()->whereIn('role', ['owner', 'manager'])->first()->workspace_id) }}"
               class="nav-item {{ request()->routeIs('workspaces.reports') ? 'active' : '' }}">📊 Reports</a>
        @else
            <a href="#" class="nav-item">📊 Reports</a>
        @endif

        @php($favoriteProjects = auth()->user()->favorites()->with('project')->get())
        @if ($favoriteProjects->isNotEmpty())
            <div class="nav-label">⭐ Favorites</div>
            @foreach ($favoriteProjects as $fav)
                <a href="{{ route('projects.show', $fav->project) }}" class="nav-item"
                   title="{{ $fav->project->name }}">
                    ⭐ {{ Str::limit($fav->project->name, 18) }}
                </a>
            @endforeach
        @endif

        <div class="spacer"></div>

        @php($pendingInvites = \App\Models\WorkspaceInvitation::where('email', auth()->user()->email)->where('status', 'pending')->count())
        @if ($pendingInvites > 0)
            <a href="{{ route('invitations.index') }}" class="nav-item" style="color:#fbbf24;">
                ✉️ Lời mời ({{ $pendingInvites }})
            </a>
        @endif

        <a href="{{ route('profile.edit') }}" class="user-box">
            <img src="{{ auth()->user()->avatarUrl() }}" alt="avatar">
            <span>
                <span class="name">{{ auth()->user()->name }}</span><br>
                <span class="email">{{ auth()->user()->email }}</span>
            </span>
        </a>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1>@yield('page_title', 'Dashboard')</h1>
            <div class="actions">
                <a href="{{ route('notifications.index') }}" class="bell" title="Notifications">🔔<span class="badge {{ auth()->user()->unreadNotifications->count() ? '' : 'badge-zero' }}">{{ auth()->user()->unreadNotifications->count() }}</span></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Logout</button>
                </form>
            </div>
        </header>

        <main class="content">
            @if (session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
