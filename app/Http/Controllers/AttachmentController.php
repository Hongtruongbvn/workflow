<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    private const MAX_SIZE_KB = 10240; // 10 MB

    /**
     * Upload a file to a task.
     */
    public function store(Request $request, Project $project, Task $task): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_SIZE_KB],
        ]);

        $file = $request->file('file');

        $task->attachments()->create([
            'user_id' => $request->user()->id,
            'file_path' => $file->store("attachments/project-{$project->id}", 'public'),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        Activity::record($project->workspace, $request->user(), 'uploaded', [
            'task' => $task->title,
            'file' => $file->getClientOriginalName(),
        ], $project, $task);

        return back()->with('status', 'Đã tải lên "'.$file->getClientOriginalName().'".');
    }

    /**
     * Download an attachment.
     */
    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);

        abort_unless(
            Storage::disk('public')->exists($attachment->file_path),
            404, 'File không tồn tại.'
        );

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete an attachment (uploader or manager+).
     */
    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        $this->authorizeAttachment($request, $attachment);

        $isUploader = $attachment->user_id === $request->user()->id;
        $isManager = $request->user()->canManageWorkspace($attachment->task->project->workspace);

        if (! $isUploader && ! $isManager) {
            return back()->with('error', 'Bạn chỉ có thể xóa file của mình.');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('status', 'Đã xóa file "'.$attachment->file_name.'".');
    }

    private function authorizeAttachment(Request $request, Attachment $attachment): void
    {
        $user = $request->user();
        $project = $attachment->task->project;
        $role = $user->workspaceRole($project->workspace);

        $allowed = in_array($role, ['owner', 'manager'])
            || $project->members()->where('users.id', $user->id)->exists();

        abort_unless($allowed, 403, 'Bạn không có quyền truy cập file này.');
    }
}
