<?php

namespace App\Services\GoogleClient\Google;

use Google_Client;
use Google_Service_Drive;

class GoogleDriveService
{
    protected Google_Client $client;

    protected Google_Service_Drive $drive;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        $this->client = new Google_Client;
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType('offline');

        // Charger le token existant si disponible
        $this->loadToken();

        $this->drive = new Google_Service_Drive($this->client);
    }

    protected function loadToken(): void
    {
        $token = session('google_drive_token');
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    /**
     * Récupérer les métadonnées d'un fichier
     */
    public function getFileInfo(string $fileId): ?array
    {
        try {
            $file = $this->drive->files->get($fileId, [
                'fields' => $this->getFileFields(),
            ]);

            return $this->formatFileData($file);

        } catch (\Exception $e) {
            \Log::error('Google Drive Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Champs à récupérer (optimise la requête)
     */
    protected function getFileFields(): string
    {
        return implode(',', [
            'id',
            'name',
            'description',
            'mimeType',
            'shared',
            'createdTime',
            'modifiedTime',
            'viewedByMeTime',
            'lastModifyingUser',
            'size',
            'quotaBytesUsed',
            'md5Checksum',
            'thumbnailLink',
            'webContentLink',
            'webViewLink',
            'iconLink',
            'hasThumbnail',
            'thumbnailVersion',
            'viewersCanCopyContent',
            'copyRequiresWriterPermission',
            'writersCanShare',
            'permissions',
            'parents',
            'owners',
            'lastModifyingUser',
        ]);
    }

    /**
     * Formater les données du fichier
     */
    protected function formatFileData($file): array
    {
        $owners = $file->getOwners() ?? [];
        $parents = $file->getParents() ?? [];
        $permissions = $file->getPermissions() ?? [];

        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'description' => $file->getDescription(),
            'mime_type' => $file->getMimeType(),
            'is_folder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
            'shared' => $file->getShared(),

            // Dates
            'created_at' => $file->getCreatedTime(),
            'modified_at' => $file->getModifiedTime(),
            'viewed_at' => $file->getViewedByMeTime(),

            // Taille (en octets)
            'size' => $file->getSize(),
            'size_human' => $this->formatBytes($file->getSize()),
            'quota_bytes_used' => $file->getQuotaBytesUsed(),

            // Hash
            'md5_checksum' => $file->getMd5Checksum(),

            // URLs
            'thumbnail_url' => $file->getThumbnailLink(),
            'web_content_url' => $file->getWebContentLink(),
            'web_view_url' => $file->getWebViewLink(),
            'icon_url' => $file->getIconLink(),

            // Permissions
            'viewers_can_copy' => $file->getViewersCanCopyContent(),
            'copy_requires_writer' => $file->getCopyRequiresWriterPermission(),
            'writers_can_share' => $file->getWritersCanShare(),
            'has_thumbnail' => $file->getHasThumbnail(),

            // Relations
            'owners' => array_map(fn ($o) => [
                'id' => $o->getId(),
                'email' => $o->getEmailAddress(),
                'name' => $o->getDisplayName(),
                'photo' => $o->getPhotoLink(),
            ], $owners),

            'parents' => $parents,
            'permissions' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'type' => $p->getType(),
                'role' => $p->getRole(),
                'email' => $p->getEmailAddress(),
            ], $permissions),

            'last_modified_by' => $file->getLastModifyingUser() ? [
                'id' => $file->getLastModifyingUser()->getId(),
                'email' => $file->getLastModifyingUser()->getEmailAddress(),
                'name' => $file->getLastModifyingUser()->getDisplayName(),
            ] : null,
        ];
    }

    /**
     * Formater les octets en taille lisible
     */
    protected function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
