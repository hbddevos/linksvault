<?php

namespace App\Services\GoogleClient\Google;

use Google_Client;
use Google_Service_Drive;

class GoogleDrivePublicService
{
    protected Google_Client $client;
    protected Google_Service_Drive $drive;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey(config('google.api_key'));
        $this->drive = new Google_Service_Drive($this->client);
    }

    /**
     * Récupérer les infos d'un fichier public
     */
    public function getPublicFileInfo(string $fileId): ?array
    {
        try {
            // Pour les fichiers publics, utiliser "anyone" avec le lien de lecture
            $file = $this->drive->files->get($fileId, [
                'fields' => 'id,name,description,mimeType,size,createdTime,modifiedTime,thumbnailLink,webViewLink,iconLink,hasThumbnail,owners,permissions',
            ]);

            return $this->formatFileData($file);

        } catch (\Exception $e) {
            \Log::error('Public File Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formater les données
     */
    protected function formatFileData($file): array
    {
        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'description' => $file->getDescription(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'size_human' => $this->formatBytes($file->getSize()),
            'created_at' => $file->getCreatedTime(),
            'modified_at' => $file->getModifiedTime(),
            'thumbnail_url' => $file->getThumbnailLink(),
            'web_view_url' => $file->getWebViewLink(),
            'icon_url' => $file->getIconLink(),
            'has_thumbnail' => $file->getHasThumbnail(),
            'owners' => array_map(fn($o) => $o->getDisplayName(), $file->getOwners() ?? []),
        ];
    }

    protected function formatBytes(?int $bytes): string
    {
        if (!$bytes) return 'N/A';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}