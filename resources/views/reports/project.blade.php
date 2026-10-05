@extends('layouts.app')

@section('page_title', $project->name.' — Report')

@section('content')
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
        <span class="text-muted text-sm">
            <a href="{{ route('workspaces.show', $project->workspace) }}">{{ $project->workspace->name }}</a> /
            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a> /
        </span>
    </div>
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
        <h1 style="font-size:22px;">📊 Project Report</h1>
        <div style="margin-left:auto;">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">← Project</a>
        </div>
    </div>

    <div class="card mb-20">
        <h2 style="display:flex; justify-content:space-between;">
            <span>Tiến độ</span>
            <span>{{ $progress }}%</span>
        </h2>
        <div class="progress-track">
            <div class="progress-fill" style="width: {{ $progress }}%;"></div>
        </div>
    </div>

    <div class="grid cols-4 mb-20">
        <div class="card stat"><span class="num">{{ $stats['total'] }}</span><span class="label">Total Tasks</span></div>
        <div class="card stat"><span class="num done">{{ $stats['done'] }}</span><span class="label">Completed</span></div>
        <div class="card stat"><span class="num progress">{{ $stats['in_progress'] }}</span><span class="label">In Progress</span></div>
        <div class="card stat"><span class="num todo">{{ $stats['todo'] }}</span><span class="label">Todo</span></div>
    </div>

    @if ($stats['overdue'] > 0)
        <div class="alert error mb-20">⚠ {{ $stats['overdue'] }} task(s) đang quá hạn!</div>
    @endif

    <div class="grid cols-2">
        <div class="card">
            <h2>👥 Member Performance</h2>
            <table class="report-table">
                <tr><th>Thành viên</th><th>Completed</th><th>In Progress</th><th>Todo</th><th>Overdue</th></tr>
                @forelse ($members as $row)
                    <tr>
                        <td>
                            <img src="{{ $row['user']->avatarUrl() }}" alt="" style="width:22px;height:22px;border-radius:50%;vertical-align:middle;margin-right:6px;">
                            {{ $row['user']->name }}
                        </td>
                        <td class="num-done">{{ $row['completed'] }}</td>
                        <td>{{ $row['in_progress'] }}</td>
                        <td>{{ $row['todo'] }}</td>
                        <td class="{{ $row['overdue'] > 0 ? 'num-overdue' : '' }}">{{ $row['overdue'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Chưa có thành viên</td></tr>
                @endforelse
            </table>
            <p class="text-muted text-sm" style="margin-top:10px;">
                Mục đích: giúp Manager biết công việc đang được phân bổ như thế nào.
            </p>
        </div>

        <div>
            <div class="card">
                <h2>📈 Task hoàn thành theo tuần</h2>
                @forelse ($timeline as $week)
                    <div class="task-row">
                        <span class="text-muted text-sm">{{ $week['label'] }}</span>
                        <span>{{ str_repeat('█', min($week['completed'], 20)) }} {{ $week['completed'] }}</span>
                    </div>
                @empty
                    <div class="empty">Không có dữ liệu</div>
                @endforelse
            </div>

            <div class="card mt-20">
                <h2>🎯 Milestones</h2>
                @forelse ($milestones as $milestone)
                    @php($mp = $milestone->tasks_count > 0 ? (int) round($milestone->tasks_done / $milestone->tasks_count * 100) : 0)
                    <div class="task-row">
                        <span>{{ $milestone->name }} {{ $milestone->completed_at ? '✓' : '' }}</span>
                        <span class="text-muted text-sm">{{ $mp }}%</span>
                    </div>
                @empty
                    <div class="empty">Chưa có milestone</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
