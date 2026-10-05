<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information (name + email).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // max 2MB
        ]);

        $user = $request->user();

        // Delete old avatar if it exists.
        if ($user->avatar_path && \Storage::disk('public')->exists($user->avatar_path)) {
            \Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        return back()->with('status', 'avatar-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Update the user's notification preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_task_assigned' => ['sometimes', 'boolean'],
            'notify_mention' => ['sometimes', 'boolean'],
            'notify_deadline_reminder' => ['sometimes', 'boolean'],
            'notify_project_updates' => ['sometimes', 'boolean'],
        ]);

        $request->user()->settings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'notify_task_assigned' => $request->boolean('notify_task_assigned'),
                'notify_mention' => $request->boolean('notify_mention'),
                'notify_deadline_reminder' => $request->boolean('notify_deadline_reminder'),
                'notify_project_updates' => $request->boolean('notify_project_updates'),
            ]
        );

        return back()->with('status', 'preferences-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'account-deleted');
    }
}
