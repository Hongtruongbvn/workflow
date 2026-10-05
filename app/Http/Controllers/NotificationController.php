<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List the user's notifications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'unread' => $user->unreadNotifications()->latest()->get(),
            'read' => $user->readNotifications()->latest()->take(20)->get(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    /**
     * Mark every notification as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Đã đánh dấu tất cả là đã đọc.');
    }
}
