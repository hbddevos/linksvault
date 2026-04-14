<?php

namespace App\Actions\LinkShareActions;

use App\Models\LinkShare;
use Illuminate\Database\Eloquent\Collection;

class GetSharedLinksAction
{
    /**
     * Récupérer les liens que l'utilisateur a partagés.
     */
    public function getSentLinks(int $userId, array $filters = []): Collection
    {
        $query = LinkShare::with(['link.category', 'link.tags', 'recipient'])
            ->sentBy($userId)
            ->orderByDesc('created_at');

        // Appliquer les filtres
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Récupérer les liens partagés avec l'utilisateur.
     */
    public function getReceivedLinks(int $userId, array $filters = []): Collection
    {
        $query = LinkShare::with(['link.category', 'link.tags', 'sender'])
            ->receivedBy($userId)
            ->valid()
            ->orderByDesc('created_at');

        // Appliquer les filtres
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Obtenir les statistiques de partage.
     */
    public function getStats(int $userId): array
    {
        $sent = LinkShare::sentBy($userId);
        $received = LinkShare::receivedBy($userId)->valid();

        return [
            'total_sent' => $sent->count(),
            'total_received' => $received->count(),
            'sent_this_week' => $sent->where('created_at', '>=', now()->startOfWeek())->count(),
            'received_this_week' => $received->where('created_at', '>=', now()->startOfWeek())->count(),
            'most_shared_link' => $this->getMostSharedLink($userId),
            'recent_activity' => $this->getRecentActivity($userId),
        ];
    }

    /**
     * Obtenir le lien le plus partagé.
     */
    protected function getMostSharedLink(int $userId): ?array
    {
        $mostShared = LinkShare::sentBy($userId)
            ->selectRaw('link_id, count(*) as share_count')
            ->groupBy('link_id')
            ->orderByDesc('share_count')
            ->with('link')
            ->first();

        if ($mostShared && $mostShared->link) {
            return [
                'link' => $mostShared->link,
                'share_count' => $mostShared->share_count,
            ];
        }

        return null;
    }

    /**
     * Obtenir l'activité récente.
     */
    protected function getRecentActivity(int $userId): Collection
    {
        return LinkShare::with(['link', 'sender', 'recipient'])
            ->where(function ($query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhere('recipient_user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
