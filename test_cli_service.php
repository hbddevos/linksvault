<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GoogleClient\YouTube\YouTubeTranscriptCliService;

$service = new YouTubeTranscriptCliService;

echo "=== Test 1: Vidéo courte anglaise (hUSu4bWYtpA) ===\n";
$result1 = $service->getTranscript('hUSu4bWYtpA', ['en']);

if ($result1) {
    echo "✓ Succès!\n";
    echo "Langue: {$result1['language']}\n";
    echo 'Segments: '.count($result1['segments'])."\n";
    echo 'Texte complet (200 premiers chars): '.substr($result1['full_text'], 0, 200)."...\n\n";
} else {
    echo "✗ Échec\n\n";
}

echo "=== Test 2: Vidéo française (ReqHcXhYzWA) ===\n";
$result2 = $service->getTranscript('ReqHcXhYzWA', ['fr', 'en']);

if ($result2) {
    echo "✓ Succès!\n";
    echo "Langue: {$result2['language']}\n";
    echo 'Segments: '.count($result2['segments'])."\n";
    echo 'Texte complet (200 premiers chars): '.substr($result2['full_text'], 0, 200)."...\n\n";
} else {
    echo "✗ Échec\n\n";
}

echo "=== Test 3: Texte tronqué (max 50 mots) ===\n";
if ($result2) {
    $truncated = $service->truncateText($result2['full_text'], 50);
    echo "Texte tronqué: {$truncated}\n\n";
}

echo "=== Test 4: getPlainText avec troncature ===\n";
$plainText = $service->getPlainText('ReqHcXhYzWA', ['fr', 'en'], 100);
if ($plainText) {
    echo "Plain text (100 mots max):\n{$plainText}\n";
} else {
    echo "✗ Échec\n";
}

echo "=== Test 5: Vidéo multi-langues (52Orbt9Z-B8) ===\n";
$result3 = $service->getTranscript('52Orbt9Z-B8', ['fr', 'en']);

if ($result3) {
    echo "✓ Succès!\n";
    echo "Langue: {$result3['language']}\n";
    echo 'Segments: '.count($result3['segments'])."\n";
    echo 'Texte (150 premiers chars): '.substr($result3['full_text'], 0, 150)."...\n\n";
} else {
    echo "✗ Échec\n\n";
}
