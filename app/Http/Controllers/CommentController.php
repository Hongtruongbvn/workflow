<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\UserMentionedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommentController extends Controller
{
    /**
     * Store a new comment (optionally a reply).
     */
    public function store(Request $request, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = Comment::findOrFail($validated['parent_id']);

            if ($parent->task_id !== $task->id) {
                return back()->with('error', 'Reply không hợp lệ.');
            }
        }

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Notify @mentioned project members (excluding the author, respecting preferences).
        $this->mentionedUsers($comment->body, $project)
            ->reject(fn ($user) => $user->id === $request->user()->id)
            ->filter(fn ($user) => $user->wantsNotification('notify_mention'))
            ->each(fn ($user) => $user->notify(new UserMentionedNotification($comment, $task)));

        Activity::record($project->workspace, $request->user(), 'commented', [
            'task' => $task->title,
        ], $project, $task);

        return back()->with('status', 'Đã thêm bình luận.');
    }

    /**
     * Update a comment (author only).
     */
    public function update(Request $request, Project $project, Comment $comment): RedirectResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return back()->with('error', 'Bạn chỉ có thể sửa bình luận của mình.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update($validated);

        return back()->with('status', 'Đã cập nhật bình luận.');
    }

    /**
     * Delete a comment (author or manager+).
     */
    public function destroy(Request $request, Project $project, Comment $comment): RedirectResponse
    {
        $isAuthor = $comment->user_id === $request->user()->id;
        $isManager = $request->user()->canManageWorkspace($project->workspace);

        if (! $isAuthor && ! $isManager) {
            return back()->with('error', 'Bạn không có quyền xóa bình luận này.');
        }

        // Delete replies as well.
        $comment->replies()->delete();
        $comment->delete();

        return back()->with('status', 'Đã xóa bình luận.');
    }

    /**
     * Find project members mentioned with @name in the body.
     */
    private function mentionedUsers(string $body, Project $project): Collection
    {
        preg_match_all('/@([\p{L}0-9_]+)/u', $body, $matches);

        if (empty($matches[1])) {
            return collect();
        }

        $tokens = collect($matches[1])
            ->map(fn ($token) => mb_strtolower($token))
            ->unique();

        return $project->members->filter(function (User $user) use ($tokens) {
            $name = mb_strtolower($user->name);
            $words = collect(preg_split('/\s+/', $name) ?: []);

            return $tokens->contains(
                fn ($token) => $token === $name || $words->contains($token) || str_starts_with($name, $token)
            );
        });
    }
}
