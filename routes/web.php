<?php

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [SessionController::class, 'create'])->name('login');
    Route::post('login', [SessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Authenticated application
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::patch('profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |------------------------------------------------------------------
    | Workspaces
    |------------------------------------------------------------------
    */
    Route::get('workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    Route::middleware('workspace.member')->group(function () {
        Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])->name('workspaces.members');
    });

    Route::middleware('workspace.owner')->group(function () {
        Route::get('workspaces/{workspace}/settings', [WorkspaceController::class, 'edit'])->name('workspaces.edit');
        Route::patch('workspaces/{workspace}/settings', [WorkspaceController::class, 'update'])->name('workspaces.update');
        Route::patch('workspaces/{workspace}/logo', [WorkspaceController::class, 'updateLogo'])->name('workspaces.logo');
        Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');

        Route::patch('workspaces/{workspace}/members/{member}', [WorkspaceMemberController::class, 'updateRole'])
            ->name('workspaces.members.update');
        Route::delete('workspaces/{workspace}/members/{member}', [WorkspaceMemberController::class, 'destroy'])
            ->name('workspaces.members.destroy');
        Route::delete('invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy'])
            ->name('invitations.destroy');
    });

    Route::middleware('workspace.manager')->group(function () {
        Route::post('workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store'])
            ->name('workspaces.invitations.store');
    });

    Route::get('my-invitations', [WorkspaceInvitationController::class, 'index'])->name('invitations.index');

    Route::get('my-tasks', [MyTaskController::class, 'index'])->name('tasks.my');
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('trash/{taskId}/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('trash/{taskId}', [TrashController::class, 'destroy'])->name('trash.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.read');

    /*
    |------------------------------------------------------------------
    | Projects
    |------------------------------------------------------------------
    */
    Route::middleware('workspace.manager')->group(function () {
        Route::get('workspaces/{workspace}/projects/create', [ProjectController::class, 'create'])
            ->name('projects.create');
        Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])
            ->name('projects.store');
    });

    Route::middleware('project.member')->group(function () {
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/board', [ProjectController::class, 'board'])->name('projects.board');
        Route::post('projects/{project}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    });

    Route::middleware('project.manager')->group(function () {
        Route::get('projects/{project}/report', [ReportController::class, 'project'])->name('projects.report');
    });

    Route::middleware('workspace.manager')->group(function () {
        Route::get('workspaces/{workspace}/reports', [ReportController::class, 'workspace'])->name('workspaces.reports');
    });

    Route::middleware('project.manager')->group(function () {
        Route::get('projects/{project}/settings', [ProjectController::class, 'edit'])->name('projects.settings');
        Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
        Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
        Route::delete('projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])
            ->name('projects.members.destroy');

        Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    });

    Route::middleware('project.member')->group(function () {
        Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::post('projects/{project}/tasks/{task}/move', [TaskController::class, 'move'])
            ->name('tasks.move');

        Route::post('projects/{project}/tasks/{task}/subtasks', [SubtaskController::class, 'store'])
            ->name('subtasks.store');
        Route::patch('projects/{project}/subtasks/{subtask}', [SubtaskController::class, 'update'])
            ->name('subtasks.update');
        Route::delete('projects/{project}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])
            ->name('subtasks.destroy');

        Route::post('projects/{project}/tasks/{task}/labels', [LabelController::class, 'attach'])
            ->name('task-labels.attach');
        Route::delete('projects/{project}/tasks/{task}/labels/{label}', [LabelController::class, 'detach'])
            ->name('task-labels.detach');

        Route::post('projects/{project}/tasks/{task}/comments', [CommentController::class, 'store'])
            ->name('comments.store');
        Route::patch('projects/{project}/comments/{comment}', [CommentController::class, 'update'])
            ->name('comments.update');
        Route::delete('projects/{project}/comments/{comment}', [CommentController::class, 'destroy'])
            ->name('comments.destroy');

        Route::post('projects/{project}/tasks/{task}/attachments', [AttachmentController::class, 'store'])
            ->name('attachments.store');
    });

    // Attachment download/delete: authorize inside the controller (no {project} param).
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');

    Route::middleware('project.manager')->group(function () {
        Route::delete('projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::post('projects/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
        Route::patch('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update'])
            ->name('milestones.update');
        Route::delete('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])
            ->name('milestones.destroy');
    });

    Route::middleware('workspace.manager')->group(function () {
        Route::post('workspaces/{workspace}/labels', [LabelController::class, 'store'])->name('labels.store');
        Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Invitations (accessible before login — show the invitation page)
|--------------------------------------------------------------------------
*/

Route::get('invitations/{token}', [WorkspaceInvitationController::class, 'show'])->name('invitations.show');

Route::middleware('auth')->group(function () {
    Route::post('invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('invitations/{token}/decline', [WorkspaceInvitationController::class, 'decline'])->name('invitations.decline');

    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
});
