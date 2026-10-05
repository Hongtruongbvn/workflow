<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ------------------------------------------------------------------
     |  Relationships
     * ------------------------------------------------------------------ */

    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function workspaceMemberships()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Whether the user wants a given notification type.
     * Defaults to true when no settings row exists yet.
     */
    public function wantsNotification(string $key): bool
    {
        $settings = $this->settings;

        if (! $settings) {
            return true;
        }

        return (bool) $settings->{$key};
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withTimestamps();
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function recentlyViewed()
    {
        return $this->hasMany(RecentlyViewed::class)->latest('viewed_at');
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------ */

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=80";
    }

    public function workspaceRole(Workspace $workspace): ?string
    {
        $member = $this->workspaceMemberships()->where('workspace_id', $workspace->id)->first();

        return $member?->role;
    }

    public function isOwnerOf(Workspace $workspace): bool
    {
        return $this->workspaceRole($workspace) === 'owner';
    }

    public function canManageWorkspace(Workspace $workspace): bool
    {
        return in_array($this->workspaceRole($workspace), ['owner', 'manager']);
    }
}
