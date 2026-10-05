<x-mail::message>
# Bạn được mời tham gia workspace! 🎉

**{{ $invitation->inviter->name }}** đã mời bạn tham gia workspace **{{ $invitation->workspace->name }}**
với quyền **{{ $invitation->role }}**.

{{ $invitation->workspace->description }}

<x-mail::button :url="route('invitations.show', $invitation->token)">
Xem lời mời
</x-mail::button>

Lời mời này có hiệu lực đến {{ $invitation->expires_at->format('d/m/Y H:i') }}.

Cảm ơn,<br>
{{ config('app.name') }}
</x-mail::message>
