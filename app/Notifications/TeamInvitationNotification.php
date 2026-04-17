<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use LaravelDaily\FilaTeams\Models\TeamInvitation;

class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TeamInvitation $invitation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = $this->invitation->expires_at
            ? URL::temporarySignedRoute(
                'filateams.invitations.accept',
                $this->invitation->expires_at,
                ['code' => $this->invitation->code]
            )
            : URL::signedRoute('filateams.invitations.accept', ['code' => $this->invitation->code]);

        return (new MailMessage)
            ->subject(__('filateams::filateams.mail.invitation.subject', ['team' => $this->invitation->team->name]))
            ->line(__('filateams::filateams.mail.invitation.line_invited', ['inviter' => $this->invitation->inviter->name, 'team' => $this->invitation->team->name]))
            ->action(__('filateams::filateams.mail.invitation.action_accept'), $acceptUrl)
            ->line(__('filateams::filateams.mail.invitation.line_expiry', ['date' => $this->invitation->expires_at->format('F j, Y')]));
    }
}
