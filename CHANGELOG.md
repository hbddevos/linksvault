# Changelog

## 2026-04-14

### Features
- **YouTube Transcription Multi-langue** : Système intelligent de récupération des transcriptions YouTube
  - Script Python amélioré avec stratégie de fallback multi-niveaux
  - Support automatique de toutes les langues (fr, en, es, de, etc.)
  - Normalisation des codes de langue BCP 47 vers ISO 639-1
  - Détection et affichage des langues disponibles en cas d'échec
  - Logging détaillé de la stratégie utilisée pour le débogage
  - Cache Laravel (24h) pour optimiser les performances
- **YouTube Transcription CLI Native** : Nouvelle approche simplifiée utilisant directement la commande `youtube_transcript_api`
  - Service dédié `YouTubeTranscriptCliService` appelant directement la CLI
  - Commande : `youtube_transcript_api VIDEO_ID --languages fr en`
  - Wrapper Python intelligent pour parser la sortie brute (format Python nested lists)
  - Gestion robuste de l'encodage UTF-8 sur Windows (évite erreur charmap)
  - Fallback multi-langues natif géré par la bibliothèque elle-même
  - Troncature automatique du texte (1000 mots max) avant envoi à l'IA
  - Coexistence avec l'ancien service pour flexibilité maximale
- **Intégration Filament** : Génération de résumés AI depuis l'interface
  - Bouton "Generate AI Summary" dans le formulaire des liens
  - Utilisation du nouveau service CLI avec troncature intelligente
  - Affichage des métadonnées (titre vidéo, langue détectée)
  - Messages d'erreur informatifs avec liste des langues disponibles
  - Gestion robuste des exceptions avec logging complet

### Technical
- **Architecture transcription** : Deux approches complémentaires
  - **Approche manuelle** (`get_transcript.py` + `YouTubeTranscriptService`) : Contrôle total sur le fallback
  - **Approche CLI** (`YouTubeTranscriptCliService`) : Simplicité avec wrapper Python pour parsing
  - Formulaire Filament (`LinkForm`) : Utilise maintenant l'approche CLI par défaut
- **Parsing sortie Python** : Solution robuste pour Windows
  - Écriture dans fichier temporaire pour éviter limite 8192 bytes de `echo`
  - Forçage UTF-8 via `io.TextIOWrapper` pour éviter erreur charmap
  - Utilisation de `ast.literal_eval` pour parser en toute sécurité
  - Conversion automatique en JSON propre avec `ensure_ascii=False`
- **Normalisation des langues** : Conversion automatique des formats
  - YouTube API retourne parfois 'fr-FR', 'en-US' → normalisé en 'fr', 'en'
  - Fallback automatique : langue demandée → anglais → première disponible
- **Optimisation IA** : Troncature intelligente
  - Limite à 1000 mots maximum avant envoi à l'agent AI
  - Méthode `truncateText()` réutilisable avec paramètre configurable
  - Évite les dépassements de contexte et réduit les coûts API
- **Amélioration UX** : Feedback clair à l'utilisateur
  - Emojis pour identifier rapidement le statut (✅ ⚠️ ❌ ℹ️)
  - Métadonnées affichées avec le résumé AI
  - Stratégie de récupération transparente pour l'utilisateur

## 2026-04-12

### Features