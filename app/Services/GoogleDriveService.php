<?php

namespace App\Services;

use App\Models\GoogleDrive as GoogleDriveModel;
use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    /**
     * Cache TTL for folder listings in seconds (5 minutes).
     */
    protected const FOLDER_CACHE_TTL = 300;

    /**
     * Google Drive app folder name.
     */
    protected const APP_FOLDER_NAME = 'LinksVault';

    public function __construct(
        protected LinkService $linkService,
    ) {}

    /**
     * Get configured Google API client.
     */
    public function getClient(?string $accessToken = null): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(route('google.drive.callback'));
        $client->addScope(Drive::DRIVE_FILE);
        $client->addScope(Drive::DRIVE_APPDATA);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($accessToken) {
            $client->setAccessToken($accessToken);
        }

        return $client;
    }

    /**
     * Get the OAuth authorization URL.
     */
    public function getAuthorizationUrl(User $user): string
    {
        $client = $this->getClient();

        // Store state to prevent CSRF
        $state = base64_encode(json_encode([
            'user_id' => $user->id,
            'csrf_token' => bin2hex(random_bytes(16)),
        ]));

        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and store tokens.
     *
     * @param  string  $code  The OAuth authorization code
     */
    public function handleCallback(User $user, string $code): GoogleDriveModel
    {
        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException("Google OAuth error: {$token['error']}");
        }

        $client->setAccessToken($token);

        // Get user's Google account email
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        $email = $userInfo->getEmail();

        // Create app folder if it doesn't exist
        $folderId = $this->getOrCreateAppFolder($client);

        $driveRecord = GoogleDriveModel::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $client->getAccessToken(),
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                'root_folder_id' => $folderId,
                'email' => $email,
            ]
        );

        return $driveRecord;
    }

    /**
     * Refresh the access token if expired.
     */
    public function refreshToken(GoogleDriveModel $driveRecord): GoogleDriveModel
    {
        if (! $driveRecord->isTokenExpired()) {
            return $driveRecord;
        }

        $accessToken = json_decode($driveRecord->access_token, true);

        if (! isset($accessToken['refresh_token'])) {
            throw new \RuntimeException('No refresh token available');
        }

        $client = $this->getClient($driveRecord->access_token);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
            $newToken = $client->getAccessToken();

            $driveRecord->update([
                'access_token' => $newToken,
                'expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);
        }

        return $driveRecord->fresh();
    }

    /**
     * Upload a file to Google Drive.
     *
     * @param  User  $user  The user who owns the Drive connection
     * @param  string  $filePath  Local file path (relative to storage disk)
     * @param  string|null  $folderId  Target folder ID (null = app folder)
     * @return array{file_id: string, file_name: string, web_view_link: string, web_content_link: string, mime_type: string}
     */
    public function uploadFile(User $user, string $filePath, ?string $folderId = null): array
    {
        $driveRecord = $this->getUserDrive($user);
        $this->refreshToken($driveRecord);

        $client = $this->getClient($driveRecord->access_token);
        $service = new Drive($client);

        // Resolve full path
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$fullPath}");
        }

        $fileName = basename($filePath);
        $mimeType = $this->detectMimeType($fullPath);
        $targetFolderId = $folderId ?? $driveRecord->root_folder_id;

        $fileMetadata = new DriveFile;
        $fileMetadata->setName($fileName);

        if ($targetFolderId) {
            $fileMetadata->setParents([$targetFolderId]);
        }

        $content = file_get_contents($fullPath);

        $createdFile = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
        ]);

        // Make file accessible if needed
        if ($targetFolderId) {
            $permission = new Drive\Permission;
            $permission->setRole('reader');
            $permission->setType('anyone');
            $service->permissions->create($createdFile->getId(), $permission);
        }

        return [
            'file_id' => $createdFile->getId(),
            'file_name' => $createdFile->getName(),
            'web_view_link' => $createdFile->getWebViewLink(),
            'web_content_link' => $createdFile->getWebContentLink(),
            'mime_type' => $createdFile->getMimeType(),
        ];
    }

    /**
     * List folders in Google Drive.
     *
     * @return array<int, array{id: string, name: string, created_time: string}>
     */
    public function listFolders(User $user): array
    {
        $driveRecord = $this->getUserDrive($user);
        $this->refreshToken($driveRecord);

        $cacheKey = "drive.folders.{$user->id}";

        return Cache::remember($cacheKey, self::FOLDER_CACHE_TTL, function () use ($driveRecord) {
            $client = $this->getClient($driveRecord->access_token);
            $service = new Drive($client);

            $response = $service->files->listFiles([
                'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false",
                'fields' => 'files(id, name, createdTime)',
                'pageSize' => 100,
            ]);

            return collect($response->getFiles())
                ->map(fn ($file) => [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'created_time' => $file->getCreatedTime(),
                ])
                ->all();
        });
    }

    /**
     * Disconnect user's Google Drive account.
     */
    public function disconnect(User $user): bool
    {
        $driveRecord = $user->googleDrive;

        if (! $driveRecord) {
            return false;
        }

        // Revoke token with Google
        try {
            $accessToken = json_decode($driveRecord->access_token, true);
            if (isset($accessToken['access_token'])) {
                Http::post('https://oauth2.googleapis.com/revoke', [
                    'token' => $accessToken['access_token'],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to revoke Google Drive token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $driveRecord->delete();
    }

    /**
     * Check if user has connected their Google Drive.
     */
    public function isConnected(User $user): bool
    {
        return $user->googleDrive !== null;
    }

    /**
     * Get user's Google Drive connection record.
     */
    public function getUserDrive(User $user): GoogleDriveModel
    {
        $drive = $user->googleDrive;

        if (! $drive) {
            throw new \RuntimeException('Google Drive not connected for this user');
        }

        return $drive;
    }

    /**
     * Get or create the app's default folder.
     */
    protected function getOrCreateAppFolder(Client $client): string
    {
        $service = new Drive($client);

        $response = $service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and name='".self::APP_FOLDER_NAME."' and trashed=false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        $files = $response->getFiles();

        if (! empty($files)) {
            return $files[0]->getId();
        }

        $folderMetadata = new DriveFile;
        $folderMetadata->setName(self::APP_FOLDER_NAME);
        $folderMetadata->setMimeType('application/vnd.google-apps.folder');

        $folder = $service->files->create($folderMetadata);

        return $folder->getId();
    }

    /**
     * Detect MIME type of a file.
     */
    protected function detectMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeMap = [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'zip' => 'application/zip',
        ];

        return $mimeMap[$extension] ?? 'application/octet-stream';
    }

    /**
     * Flush folder listing cache for a user.
     */
    public function flushFolderCache(User $user): void
    {
        Cache::forget("drive.folders.{$user->id}");
    }
}
