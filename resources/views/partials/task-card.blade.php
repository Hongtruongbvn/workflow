<article class="kanban-card" draggable="true" data-task-id="{{ $task->id }}">
    <a href="{{ route('tasks.show', [$task->project, $task]) }}" class="kanban-title" draggable="false"
       onclick="event.stopPropagation();">{{ $task->title }}</a>

    @if ($task->labels->isNotEmpty())
        <div style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:6px;">
            @foreach ($task->labels as $label)
                <span class="label-chip" style="background: {{ $label->color }}22; color: {{ $label->color }}; border: 1px solid {{ $label->color }}55; font-size:10.5px; padding:1px 7px;">
                    {{ $label->name }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="kanban-meta">
        <span class="badge-pill {{ $task->priority }}">{{ $task->priority }}</span>
        @if ($task->due_date)
            <span class="due {{ $task->isOverdue() ? 'overdue' : ($task->isDueToday() ? 'today' : '') }}">
                {{ $task->due_date->format('d/m') }}
            </span>
        @endif
        @if ($task->assignee)
            <img src="{{ $task->assignee->avatarUrl() }}" alt="{{ $task->assignee->name }}"
                 title="{{ $task->assignee->name }}" class="kanban-avatar">
        @endif
    </div>
</article>
