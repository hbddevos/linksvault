<?php

namespace App\Notifications;

use App\Models\Link;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LinkSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Link $link,
        public User $sender,
        public ?string $personalMessage = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->sender->name} vous a partagé un lien")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->sender->name} vous a partagé un lien via Linksvault2.")
            ->when($this->personalMessage, function (MailMessage $mail) {
                return $mail->line("💬 Message : {$this->personalMessage}");
            })
            ->action('Voir le lien', route('links.view', $this->link))
            ->line("Titre : {$this->link->title}")
            ->line('Type : '.($this->link->content_type?->label() ?? 'Lien web'))
            ->salutation('Cordialement, L\'équipe Linksvault2');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'link_id' => $this->link->id,
            'link_title' => $this->link->title,
            'link_url' => $this->link->url,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_email' => $this->sender->email,
            'personal_message' => $this->personalMessage,
            'content_type' => $this->link->content_type?->value,
        ];
    }

    /**
     * Get the Filament notification representation.
     */
    public function toFilament(object $notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Nouveau lien partagé')
            ->body("{$this->sender->name} vous a partagé : {$this->link->title}")
            ->icon('heroicon-o-link')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label('Voir le lien')
                    ->url(route('links.view', $this->link))
                    ->markAsRead(),
            ])
            ->persistent();
    }
}
