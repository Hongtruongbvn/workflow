@extends('layouts.app')

@section('page_title', $task->title)

@section('content')
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
        <span class="text-muted text-sm">
            <a href="{{ route('workspaces.show', $project->workspace) }}">{{ $project->workspace->name }}</a> /
            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a> /
            <a href="{{ route('projects.board', $project) }}">Kanban</a> /
        </span>
    </div>
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:22px; flex-wrap:wrap;">
        <h1 style="font-size:22px;">{{ $task->title }}</h1>
        <span class="badge-pill status-{{ $task->status }}">{{ str_replace('_', ' ', $task->status) }}</span>
        <span class="badge-pill {{ $task->priority }}">{{ $task->priority }}</span>
        @if ($task->isOverdue())
            <span class="badge-pill urgent">⚠ Overdue</span>
        @endif
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a href="{{ route('projects.board', $project) }}" class="btn btn-secondary">← Board</a>
            @if (auth()->user()->canManageWorkspace($project->workspace))
                <form method="POST" action="{{ route('tasks.destroy', [$project, $task]) }}"
                      onsubmit="return confirm('Xóa task này? (Sẽ chuyển vào Trash)')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">🗑 Xóa</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid cols-2" style="grid-template-columns: 1.6fr 1fr;">
        {{-- Cột trái --}}
        <div>
            <div class="card">
                <h2>📝 Description</h2>
                @if ($task->description)
                    <p style="white-space:pre-wrap;">{{ $task->description }}</p>
                @else
                    <p class="text-muted text-sm">Chưa có mô tả.</p>
                @endif
            </div>

            {{-- Subtasks --}}
            <div class="card mt-20">
                <h2 style="display:flex; justify-content:space-between;">
                    <span>☑️ Subtasks</span>
                    @if ($subtaskProgress['total'] > 0)
                        <span class="text-muted text-sm" style="font-weight:400;">
                            {{ $subtaskProgress['done'] }} / {{ $subtaskProgress['total'] }} ·
                            {{ $subtaskProgress['percent'] }}%
                        </span>
                    @endif
                </h2>

                @if ($subtaskProgress['total'] > 0)
                    <div class="progress-track" style="margin-bottom:14px;">
                        <div class="progress-fill" style="width: {{ $subtaskProgress['percent'] }}%;"></div>
                    </div>
                @endif

                @forelse ($task->subtasks as $subtask)
                    <div class="task-row">
                        <form method="POST" action="{{ route('subtasks.update', [$project, $subtask]) }}"
                              style="display:flex; align-items:center; gap:10px; flex:1;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="subtask-check {{ $subtask->is_completed ? 'checked' : '' }}">
                                {{ $subtask->is_completed ? '☑' : '☐' }}
                            </button>
                            <span class="{{ $subtask->is_completed ? 'subtask-done' : '' }}">{{ $subtask->title }}</span>
                        </form>
                        <form method="POST" action="{{ route('subtasks.destroy', [$project, $subtask]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding:4px 10px;">✕</button>
                        </form>
                    </div>
                @empty
                    <div class="empty">Chưa có subtask nào</div>
                @endforelse

                <form method="POST" action="{{ route('subtasks.store', [$project, $task]) }}"
                      style="display:flex; gap:8px; margin-top:14px;">
                    @csrf
                    <input type="text" name="title" placeholder="Thêm subtask mới..." required>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>
            </div>

            {{-- Comments --}}
            <div class="card mt-20">
                <h2>💬 Comments ({{ $task->comments->count() }})</h2>

                @forelse ($task->comments->whereNull('parent_id') as $comment)
                    <div class="comment">
                        <img src="{{ $comment->user->avatarUrl() }}" alt="" class="comment-avatar">
                        <div style="flex:1;">
                            <div>
                                <strong>{{ $comment->user->name }}</strong>
                                <span class="text-muted text-sm">{{ $comment->created_at->format('H:i d/m/Y') }}</span>
                            </div>
                            <div class="comment-body">{!! $comment->bodyHtml() !!}</div>

                            <div class="comment-actions">
                                @if ($comment->user_id === auth()->id())
                                    <details>
                                        <summary>Sửa</summary>
                                        <form method="POST" action="{{ route('comments.update', [$project, $comment]) }}"
                                              style="margin-top:8px;">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="body" rows="2" required>{{ $comment->body }}</textarea>
                                            <button type="submit" class="btn btn-primary" style="padding:6px 12px; margin-top:6px;">Lưu</button>
                                        </form>
                                    </details>
                                @endif

                                <details>
                                    <summary>Trả lời</summary>
                                    <form method="POST" action="{{ route('comments.store', [$project, $task]) }}"
                                          style="margin-top:8px;">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <textarea name="body" rows="2" required placeholder="Trả lời {{ $comment->user->name }}..."></textarea>
                                        <button type="submit" class="btn btn-primary" style="padding:6px 12px; margin-top:6px;">Gửi</button>
                                    </form>
                                </details>

                                @if ($comment->user_id === auth()->id() || auth()->user()->canManageWorkspace($project->workspace))
                                    <form method="POST" action="{{ route('comments.destroy', [$project, $comment]) }}"
                                          onsubmit="return confirm('Xoa binh luan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="comment-action-link danger">Xóa</button>
                                    </form>
                                @endif
                            </div>

                            {{-- Replies --}}
                            @foreach ($comment->replies as $reply)
                                <div class="comment reply">
                                    <img src="{{ $reply->user->avatarUrl() }}" alt="" class="comment-avatar">
                                    <div style="flex:1;">
                                        <div>
                                            <strong>{{ $reply->user->name }}</strong>
                                            <span class="text-muted text-sm">{{ $reply->created_at->format('H:i d/m/Y') }}</span>
                                        </div>
                                        <div class="comment-body">{!! $reply->bodyHtml() !!}</div>
                                        <div class="comment-actions">
                                            @if ($reply->user_id === auth()->id())
                                                <form method="POST" action="{{ route('comments.update', [$project, $reply]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="body" value="{{ $reply->body }}" required>
                                                    <button type="submit" class="comment-action-link">Lưu sửa</button>
                                                </form>
                                            @endif
                                            @if ($reply->user_id === auth()->id() || auth()->user()->canManageWorkspace($project->workspace))
                                                <form method="POST" action="{{ route('comments.destroy', [$project, $reply]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="comment-action-link danger">Xóa</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty">Chưa có bình luận nào. Hãy bắt đầu trao đổi!</div>
                @endforelse

                <form method="POST" action="{{ route('comments.store', [$project, $task]) }}" style="margin-top:16px;">
                    @csrf
                    <textarea name="body" rows="3" placeholder="Viết bình luận... dùng @để mention thành viên" required></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:8px;">Gửi bình luận</button>
                </form>
            </div>

            {{-- Attachments --}}
            <div class="card mt-20">
                <h2>📎 Attachments ({{ $task->attachments->count() }})</h2>

                @forelse ($task->attachments as $attachment)
                    <div class="task-row">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:22px;">{{ $attachment->icon() }}</span>
                            <div>
                                <a href="{{ route('attachments.download', $attachment) }}" style="font-weight:500;">
                                    {{ $attachment->file_name }}
                                </a>
                                <div class="text-muted text-sm">
                                    {{ $attachment->formattedSize() }} · {{ $attachment->user->name }} ·
                                    {{ $attachment->created_at->format('d/m H:i') }}
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-secondary" style="padding:5px 10px;">⬇</a>
                            @if ($attachment->user_id === auth()->id() || auth()->user()->canManageWorkspace($project->workspace))
                                <form method="POST" action="{{ route('attachments.destroy', $attachment) }}"
                                      onsubmit="return confirm('Xoa file nay?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding:5px 10px;">✕</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">Chưa có file đính kèm nào</div>
                @endforelse

                <form method="POST" action="{{ route('attachments.store', [$project, $task]) }}"
                      enctype="multipart/form-data" style="display:flex; gap:8px; align-items:flex-end; margin-top:12px;">
                    @csrf
                    <div style="flex:1;">
                        <input type="file" name="file" required>
                        @error('file')<div class="validation-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>

            {{-- Activity --}}
            <div class="card mt-20">
                <h2>🕐 Activity</h2>
                @forelse ($activities as $activity)
                    <div class="task-row">
                        <span>
                            <img src="{{ $activity->user->avatarUrl() }}" alt="" style="width:22px;height:22px;border-radius:50%;vertical-align:middle;margin-right:6px;">
                            <strong>{{ $activity->user->name }}</strong>
                            <span class="text-muted text-sm">— {{ $activity->type }}
                                @if (isset($activity->data['old']))
                                    ({{ $activity->data['old'] }} → {{ $activity->data['new'] }})
                                @endif
                            </span>
                        </span>
                        <span class="text-muted text-sm">{{ $activity->created_at->format('H:i d/m') }}</span>
                    </div>
                @empty
                    <div class="empty">Chưa có hoạt động nào</div>
                @endforelse
            </div>
        </div>

        {{-- Cột phải --}}
        <div>
            {{-- Edit form --}}
            <div class="card">
                <h2>⚙️ Chỉnh sửa</h2>

                @if (session('status'))
                    <div class="alert success">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert error">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}">
                    @csrf
                    @method('PATCH')

                    <div class="field">
                        <label for="title">Task</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $task->title) }}" required>
                    </div>

                    <div class="field">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="4">{{ old('description', $task->description) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            @foreach (\App\Models\Task::STATUSES as $s)
                                <option value="{{ $s }}" {{ old('status', $task->status) === $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            @foreach (\App\Models\Task::PRIORITIES as $p)
                                <option value="{{ $p }}" {{ old('priority', $task->priority) === $p ? 'selected' : '' }}>
                                    {{ ucfirst($p) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="assignee_id">Assignee</label>
                        <select id="assignee_id" name="assignee_id">
                            <option value="">— Chưa giao —</option>
                            @foreach ($projectMembers as $member)
                                <option value="{{ $member->id }}" {{ old('assignee_id', $task->assignee_id) == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="due_date">Due Date</label>
                        <input id="due_date" type="date" name="due_date"
                               value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                    </div>

                    <div class="field">
                        <label for="milestone_id">Milestone</label>
                        <select id="milestone_id" name="milestone_id">
                            <option value="">— Không thuộc milestone —</option>
                            @foreach ($project->milestones as $milestone)
                                <option value="{{ $milestone->id }}" {{ old('milestone_id', $task->milestone_id) == $milestone->id ? 'selected' : '' }}>
                                    {{ $milestone->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Lưu thay đổi</button>
                </form>
            </div>

            {{-- Meta info --}}
            <div class="card mt-20">
                <h2>ℹ️ Thông tin</h2>
                <div class="task-row"><span class="text-muted text-sm">Created by</span><span>{{ $task->creator?->name ?? '—' }}</span></div>
                <div class="task-row"><span class="text-muted text-sm">Assignee</span><span>{{ $task->assignee?->name ?? 'Chưa giao' }}</span></div>
                <div class="task-row">
                    <span class="text-muted text-sm">Due Date</span>
                    <span class="due {{ $task->isOverdue() ? 'overdue' : ($task->isDueToday() ? 'today' : '') }}">
                        {{ $task->due_date?->format('d/m/Y') ?? '—' }} · {{ $task->dueLabel() }}
                    </span>
                </div>
                @if ($task->completed_at)
                    <div class="task-row"><span class="text-muted text-sm">Completed</span><span class="due completed">{{ $task->completed_at->format('d/m/Y H:i') }}</span></div>
                @endif
            </div>

            {{-- Labels --}}
            <div class="card mt-20">
                <h2>🏷️ Labels</h2>

                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px;">
                    @forelse ($task->labels as $label)
                        <span class="label-chip" style="background: {{ $label->color }}22; color: {{ $label->color }}; border: 1px solid {{ $label->color }}55;">
                            {{ $label->name }}
                            <form method="POST" action="{{ route('task-labels.detach', [$project, $task, $label]) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font-weight:700;">✕</button>
                            </form>
                        </span>
                    @empty
                        <span class="text-muted text-sm">Chưa có label nào</span>
                    @endforelse
                </div>

                @if ($workspaceLabels->isNotEmpty())
                    <form method="POST" action="{{ route('task-labels.attach', [$project, $task]) }}"
                          style="display:flex; gap:8px; margin-bottom:10px;">
                        @csrf
                        <select name="label_id" required style="flex:1;">
                            <option value="">— Gắn label có sẵn —</option>
                            @foreach ($workspaceLabels as $label)
                                @unless ($task->labels->contains($label))
                                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                                @endunless
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding:8px 12px;">Gắn</button>
                    </form>
                @endif

                @if (auth()->user()->canManageWorkspace($project->workspace))
                    <form method="POST" action="{{ route('labels.store', $project->workspace) }}"
                          style="display:flex; gap:8px; align-items:center; padding-top:10px; border-top:1px solid var(--border);">
                        @csrf
                        <input type="text" name="name" placeholder="Label mới..." required style="flex:1;">
                        <input type="color" name="color" value="#6366f1" style="width:44px; padding:4px; height:38px;">
                        <button type="submit" class="btn btn-secondary" style="padding:8px 12px;">Tạo</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
