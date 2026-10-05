<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvitationAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WorkspaceInvitation $invitation,
        public User $invitee,
        public bool $declined = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->declined ? 'invitation_declined' : 'invitation_accepted',
            'title' => $this->declined ? 'Lời mời bị từ chối' : 'Lời mời được chấp nhận',
            'message' => $this->declined
                ? $this->invitee->name.' đã từ chối lời mời tham gia "'.$this->invitation->workspace->name.'"'
                : $this->invitee->name.' đã tham gia "'.$this->invitation->workspace->name.'"',
            'workspace_id' => $this->invitation->workspace_id,
            'link' => route('workspaces.members', $this->invitation->workspace_id),
        ];
    }
}
