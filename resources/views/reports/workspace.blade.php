@extends('layouts.app')

@section('page_title', $workspace->name.' — Reports')

@section('content')
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">📊 Reports — {{ $workspace->name }}</h1>
        <div style="margin-left:auto;">
            <a href="{{ route('workspaces.show', $workspace) }}" class="btn btn-secondary">← Workspace</a>
        </div>
    </div>

    <div class="card mb-20">
        <h2>📁 Tổng quan Projects</h2>
        <table class="report-table">
            <tr><th>Project</th><th>Status</th><th>Total</th><th>Done</th><th>In Progress</th><th>Overdue</th><th>Progress</th></tr>
            @forelse ($projects as $row)
                <tr>
                    <td><a href="{{ route('projects.show', $row['project']) }}">{{ $row['project']->name }}</a></td>
                    <td><span class="badge-pill status-{{ str_replace(' ', '_', $row['project']->status) }}">{{ $row['project']->status }}</span></td>
                    <td>{{ $row['stats']['total'] }}</td>
                    <td class="num-done">{{ $row['stats']['done'] }}</td>
                    <td>{{ $row['stats']['in_progress'] + $row['stats']['review'] }}</td>
                    <td class="{{ $row['stats']['overdue'] > 0 ? 'num-overdue' : '' }}">{{ $row['stats']['overdue'] }}</td>
                    <td style="min-width:120px;">
                        <div class="progress-track" style="height:8px;">
                            <div class="progress-fill" style="width: {{ $row['progress'] }}%;"></div>
                        </div>
                        <span class="text-muted text-sm">{{ $row['progress'] }}%</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Chưa có project nào</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>👥 Member Performance (toàn workspace)</h2>
        <table class="report-table">
            <tr><th>Thành viên</th><th>Vai trò</th><th>Completed</th><th>In Progress</th><th>Todo</th><th>Overdue</th></tr>
            @forelse ($members as $row)
                <tr>
                    <td>
                        <img src="{{ $row['user']->avatarUrl() }}" alt="" style="width:22px;height:22px;border-radius:50%;vertical-align:middle;margin-right:6px;">
                        {{ $row['user']->name }}
                    </td>
                    <td class="text-muted text-sm">{{ $row['user']->workspaceRole($workspace) }}</td>
                    <td class="num-done">{{ $row['completed'] }}</td>
                    <td>{{ $row['in_progress'] }}</td>
                    <td>{{ $row['todo'] }}</td>
                    <td class="{{ $row['overdue'] > 0 ? 'num-overdue' : '' }}">{{ $row['overdue'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Chưa có thành viên</td></tr>
            @endforelse
        </table>
    </div>
@endsection
