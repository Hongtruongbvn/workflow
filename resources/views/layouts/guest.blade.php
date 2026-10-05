<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WorkFlow Pro')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth-card">
        <div class="brand">
            <h1>⚡ WorkFlow Pro</h1>
            <p>@yield('subtitle', 'Quản lý công việc & dự án')</p>
        </div>

        <div class="card">
            @if (session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif

            @yield('content')
        </div>

        <div class="form-links">
            @yield('links')
        </div>
    </div>
</body>
</html>
