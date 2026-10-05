<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceInvitedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WorkspaceInvitation $invitation,
    ) {}

    /**
     * In-app (database) notification only — the email is sent separately.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workspace_invited',
            'title' => 'Lời mời tham gia workspace',
            'message' => $this->invitation->inviter->name.' đã mời bạn tham gia "'.$this->invitation->workspace->name.'"',
            'workspace_id' => $this->invitation->workspace_id,
            'link' => route('invitations.show', $this->invitation->token),
        ];
    }
}
