@extends('layouts.app')

@section('page_title', 'Trash')

@section('content')
    <div style="display:flex; align-items:center; margin-bottom:20px;">
        <h1 style="font-size:22px;">🗑 Trash — Task đã xóa ({{ $tasks->count() }})</h1>
    </div>

    <div class="card">
        @forelse ($tasks as $task)
            <div class="task-row">
                <div style="flex:1;">
                    <span class="title" style="text-decoration: line-through;">{{ $task->title }}</span>
                    <div class="text-muted text-sm">
                        {{ $task->project->name }} · xóa bởi {{ $task->creator?->name ?? '—' }}
                        · {{ $task->deleted_at->format('d/m H:i') }}
                    </div>
                </div>
                <form method="POST" action="{{ route('trash.restore', $task->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="padding:6px 12px;">↩ Khôi phục</button>
                </form>
                <form method="POST" action="{{ route('trash.destroy', $task->id) }}"
                      onsubmit="return confirm('Xóa VĨNH VIỄN task này? Không thể hoàn tác!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="padding:6px 12px;">Xóa vĩnh viễn</button>
                </form>
            </div>
        @empty
            <div class="empty">Trash trống trơn ✨</div>
        @endforelse
    </div>
@endsection
