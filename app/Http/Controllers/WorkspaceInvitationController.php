<?php

namespace App\Http\Controllers;

use App\Mail\WorkspaceInvitationMail;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\InvitationAcceptedNotification;
use App\Notifications\WorkspaceInvitedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WorkspaceInvitationController extends Controller
{
    /**
     * List pending invitations for the authenticated user's email.
     */
    public function index(Request $request): View
    {
        $invitations = WorkspaceInvitation::where('email', $request->user()->email)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['workspace', 'inviter'])
            ->latest()
            ->get();

        return view('invitations.index', [
            'invitations' => $invitations,
        ]);
    }

    /**
     * Send a workspace invitation by email.
     */
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:manager,member'],
        ]);

        $email = strtolower($validated['email']);

        // Already a member?
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $workspace->members()->where('users.id', $existingUser->id)->exists()) {
            return back()->with('error', $email.' đã là thành viên của workspace.');
        }

        // Pending invitation already exists?
        $pending = $workspace->invitations()
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($pending && $pending->isPending()) {
            Mail::to($email)->send(new WorkspaceInvitationMail($pending));

            return back()->with('status', 'Lời mời đã được gửi lại tới '.$email.'.');
        }

        // Declined/expired invitation? Refresh it instead of creating a duplicate.
        $invitation = $pending ?? $workspace->invitations()->where('email', $email)->first();

        if ($invitation) {
            $invitation->update([
                'invited_by' => $request->user()->id,
                'role' => $validated['role'],
                'token' => \Str::random(40),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
            ]);
        } else {
            $invitation = $workspace->invitations()->create([
                'invited_by' => $request->user()->id,
                'email' => $email,
                'role' => $validated['role'],
                'expires_at' => now()->addDays(7),
            ]);
        }

        Mail::to($email)->send(new WorkspaceInvitationMail($invitation));

        // If the invitee already has an account, drop them an in-app notification too.
        $existingUser?->notify(new WorkspaceInvitedNotification($invitation));

        return back()->with('status', 'Lời mời đã được gửi tới '.$email.'.');
    }

    /**
     * Show the invitation page (works for guests and members).
     */
    public function show(string $token): View
    {
        $invitation = WorkspaceInvitation::where('token', $token)
            ->with(['workspace', 'inviter'])
            ->firstOrFail();

        if (! $invitation->isPending()) {
            return view('invitations.show', [
                'invitation' => $invitation,
                'expired' => true,
            ]);
        }

        // Remember the token so the user lands back here after login/register.
        if (! Auth::check()) {
            session()->flash('pending_invite_token', $token);
        }

        return view('invitations.show', [
            'invitation' => $invitation,
            'expired' => false,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('dashboard')->with('error', 'Lời mời này không còn hiệu lực.');
        }

        if (strtolower($request->user()->email) !== $invitation->email) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn cần đăng nhập bằng tài khoản '.$invitation->email.' để chấp nhận lời mời này.');
        }

        // Already a member? Just mark accepted.
        $alreadyMember = $invitation->workspace->members()->where('users.id', $request->user()->id)->exists();

        if (! $alreadyMember) {
            $invitation->workspace->members()->attach($request->user()->id, [
                'role' => $invitation->role,
            ]);

            $invitation->workspace->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'member_joined',
                'data' => ['member' => $request->user()->name, 'via' => 'invitation'],
            ]);
        }

        $invitation->update(['status' => 'accepted']);

        $invitation->inviter->notify(new InvitationAcceptedNotification($invitation, $request->user()));

        return redirect()
            ->route('workspaces.show', $invitation->workspace)
            ->with('status', 'Bạn đã tham gia "'.$invitation->workspace->name.'"! 🎉');
    }

    /**
     * Decline the invitation.
     */
    public function decline(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

        if (strtolower($request->user()->email) !== $invitation->email) {
            return redirect()->route('dashboard')->with('error', 'Không thể xử lý lời mời này.');
        }

        $invitation->update(['status' => 'declined']);

        $invitation->inviter->notify(new InvitationAcceptedNotification($invitation, $request->user(), declined: true));

        return redirect()->route('dashboard')->with('status', 'Đã từ chối lời mời.');
    }

    /**
     * Revoke a pending invitation (Owner only, handled via middleware on the route).
     */
    public function destroy(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return back()->with('status', 'Đã thu hồi lời mời tới '.$invitation->email.'.');
    }
}
