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
- **YouTube Transcription CLI** : Nouvelle approche simplifiée avec commande native
  - Nouveau script `get_transcript_cli.py` utilisant l'interface CLI de youtube_transcript_api
  - Service dédié `YouTubeTranscriptCliService` avec support multi-langues natif
  - Fallback automatique géré par la bibliothèque elle-même
  - Syntaxe plus simple : passage direct d'un tableau de langues prioritaires
  - Coexistence avec l'ancien service pour flexibilité maximale
- **Intégration Filament** : Génération de résumés AI depuis l'interface
  - Bouton "Generate AI Summary" dans le formulaire des liens
  - Affichage des métadonnées (titre vidéo, langue, stratégie)
  - Messages d'erreur informatifs avec liste des langues disponibles
  - Gestion robuste des exceptions avec logging complet

### Technical
- **Architecture transcription** : Séparation claire des responsabilités
  - Script Python (`get_transcript.py`) : Gestion du fallback intelligent (approche manuelle)
  - Script Python CLI (`get_transcript_cli.py`) : Approche simplifiée avec API native
  - Services Laravel (`YouTubeTranscriptService`, `YouTubeTranscriptCliService`) : Exécution et cache
  - Formulaire Filament (`LinkForm`) : Interface utilisateur et AI
- **Normalisation des langues** : Conversion automatique des formats
  - YouTube API retourne parfois 'fr-FR', 'en-US' → normalisé en 'fr', 'en'
  - Fallback automatique : langue demandée → variantes → anglais → première disponible
- **Amélioration UX** : Feedback clair à l'utilisateur
  - Emojis pour identifier rapidement le statut (✅ ⚠️ ❌ ℹ️)
  - Métadonnées affichées avec le résumé AI
  - Stratégie de récupération transparente pour l'utilisateur

## 2026-04-12

### Features
- **Action Patterns** : Création des actions CRUD pour Link, Category et Tag
  - Convention de nommage `VerbeModelAction` (ex: `CreateLinkAction`, `ListCategoriesAction`)
  - Actions : CreateAction, UpdateAction, DeleteAction, ListAction, ShowAction
  - Gestion automatique du `user_id` et `team_id` lors de la création
  - Filtrage automatique par équipe grâce au global scope `BelongsToTeam`
  - Support de la suppression multiple pour DeleteAction
  - Filtrage avancé pour ListAction (recherche, catégorie, type, favoris, archivés)
- **Filament Resources** : Création des resources Links, Categories et Tags
  - Pages CRUD complètes (List, Create, Edit, View) pour chaque resource
  - Tables, formulaires et infolists configurés avec Filament v5
  - Intégration automatique du team scoping via les modèles
- **Icônes Tabler** : Installation de `daljo25/filament-tabler-icons`
  - Intégration des icônes Tabler dans l'interface Filament

### Technical
- **Team Scoping Global Scope** : Ajout du trait `BelongsToTeam` pour filtrer automatiquement les requêtes par `current_team_id`
  - Filtrage automatique de toutes les requêtes sur les modèles Link, Category, Tag, GoogleDrive
  - Scopes supplémentaires : `withoutTeamScope()`, `forTeam($teamId)`, `allTeams()`
  - Amélioration des traits `AddTeamId` et `AddUserId` avec vérification de l'authentification
- **Modèles créés** : Link, Category, Tag, GoogleDrive, Team, TeamMember
  - Relations complètes entre modèles
  - Application automatique des traits de team scoping
  - Casts et fillables configurés selon les migrations
