<?php

namespace App\Actions\LinkShareActions;

use App\Mail\LinkSharedMail;
use App\Models\Link;
use App\Models\LinkShare;
use App\Models\User;
use App\Notifications\LinkSharedNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShareLinkAction
{
    /**
     * Partager un lien avec un ou plusieurs destinataires.
     *
     * @param  Link  $link  Le lien à partager
     * @param  User  $sender  L'utilisateur qui partage
     * @param  array  $recipients  Tableau de destinataires [['email' => '...', 'user_id' => null, 'name' => '...']]
     * @param  string|null  $personalMessage  Message personnel optionnel
     * @param  int|null  $expiresInDays  Nombre de jours avant expiration (null = pas d'expiration)
     * @return array ['success' => bool, 'shares' => array, 'errors' => array]
     */
    public function execute(
        Link $link,
        User $sender,
        array $recipients,
        ?string $personalMessage = null,
        ?int $expiresInDays = null
    ): array {
        $results = [
            'success' => true,
            'shares' => [],
            'errors' => [],
        ];

        foreach ($recipients as $recipient) {
            try {
                $share = $this->createShare(
                    $link,
                    $sender,
                    $recipient,
                    $personalMessage,
                    $expiresInDays
                );

                $this->sendNotifications($share, $sender);

                $results['shares'][] = $share;

            } catch (Exception $e) {
                Log::error('Erreur lors du partage de lien', [
                    'link_id' => $link->id,
                    'sender_id' => $sender->id,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $results['errors'][] = [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ];
                $results['success'] = false;
            }
        }

        return $results;
    }

    /**
     * Créer une entrée de partage en base de données.
     */
    protected function createShare(
        Link $link,
        User $sender,
        array $recipient,
        ?string $personalMessage,
        ?int $expiresInDays
    ): LinkShare {
        // Validation de l'email
        if (! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email invalide : {$recipient['email']}");
        }

        // Vérifier si le destinataire est un utilisateur inscrit
        $recipientUser = null;
        if (! empty($recipient['user_id'])) {
            $recipientUser = User::find($recipient['user_id']);
        } else {
            // Chercher par email
            $recipientUser = User::where('email', $recipient['email'])->first();
        }

        // Générer un token unique
        $token = Str::random(64);

        // Calculer la date d'expiration
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        // Créer le partage
        $share = LinkShare::create([
            'link_id' => $link->id,
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $recipientUser?->id,
            'recipient_email' => $recipient['email'],
            'recipient_name' => $recipient['name'] ?? $recipientUser?->name,
            'personal_message' => $personalMessage,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        return $share;
    }

    /**
     * Envoyer les notifications (email + in-app si utilisateur inscrit).
     */
    protected function sendNotifications(LinkShare $share, User $sender): void
    {
        $link = $share->link;
        $shareUrl = route('links.share.redirect', ['token' => $share->token]);

        // 1. Envoyer l'email
        Mail::to($share->recipient_email)->send(
            new LinkSharedMail($link, $sender, $share, $shareUrl)
        );

        // Marquer comme envoyé
        $share->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // 2. Si le destinataire est un utilisateur inscrit, envoyer une notification in-app
        if ($share->recipient_user_id) {
            $recipient = User::find($share->recipient_user_id);

            if ($recipient) {
                $recipient->notify(
                    new LinkSharedNotification(
                        $link,
                        $sender,
                        $share->personal_message
                    )
                );
            }
        }
    }
}
