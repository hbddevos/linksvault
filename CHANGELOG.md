# Changelog

## 2026-04-17

### CI/CD & DevOps
- **Enhanced GitHub Actions Workflows** : Amélioration des workflows CI/CD avec gestion robuste des migrations
  - Création d'un workflow CI/CD complet (`cicd.yml`) combinant validation, tests, build et déploiement
  - Configuration des migrations de base de données en mode non-bloquant (`continue-on-error: true`)
  - Gestion intelligente des échecs de migration en production (migrations déjà appliquées, schema à jour)
  - Messages informatifs détaillés pour le statut des migrations sans bloquer le pipeline
  - Health check automatique post-déploiement avec retries configurables
  - Résumé de déploiement automatisé avec statut des migrations
  - Séparation claire des phases : Validation → Tests → Build → Déploiement → Notifications
  - Support des environnements multiples avec protection de la branche main
  - Optimisation du cache Composer et NPM pour accélérer les builds
  - Mise à jour des workflows existants (`ci.yml` et `cd.yml`) avec la même logique non-bloquante
  - Documentation implicite via commentaires dans les workflows

- **FTP/SFTP Deployment Support** : Migration du déploiement de SSH vers FTP/SFTP
  - Remplacement du déploiement SCP par FTP/SFTP utilisant `SamKirkland/FTP-Deploy-Action@v4.3.5`
  - Support des protocoles FTP (port 21) et SFTP (port 22) configurable
  - Conservation de l'accès SSH optionnel pour les tâches post-déploiement (Composer, migrations, cache, redémarrage services)
  - Configuration flexible : déploiement FTP pur ou hybride (FTP + SSH)
  - Exclusion automatique des fichiers sensibles (.env, .git, node_modules, tests, logs)
  - Documentation complète mise à jour pour la configuration FTP/SFTP
  - Secrets GitHub mis à jour : `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_PORT`, `FTP_PROTOCOL`, `FTP_REMOTE_PATH`
  - Secrets SSH rendus optionnels : `SSH_HOST`, `SSH_USERNAME`, `SSH_KEY`, `SSH_PORT`
  - Guide de configuration FTP vs SFTP avec recommandations de sécurité
  - Meilleures pratiques pour le déploiement sécurisé via FTP/SFTP

## 2026-04-16

### Features
- **Team Management System** : Système complet de gestion d'équipes avec invitations
  - Architecture complète FilaTeams intégrée pour la gestion multi-équipes
  - Modèle `Team` enrichi avec relations complètes (members, memberships, invitations, owner)
  - Support des slugs uniques pour les équipes avec génération automatique
  - Système d'invitations par email avec tokens sécurisés
  - Modèle `TeamInvitation` avec factory et migration dédiée
  - Contrôleur `AcceptInvitationController` pour accepter les invitations via liens signés
  - Notification `TeamInvitationNotification` pour informer les utilisateurs invités
  - Middleware `EnsureTeamMembership` pour vérifier l'appartenance aux équipes
  - Policy `TeamPolicy` pour la gestion des autorisations au niveau équipe
  - Pages Filament dédiées : CreateTeamPage et EditTeam pour la gestion d'équipe
  - Configuration tenant-aware dans AppPanelProvider avec support multi-tenancy
  - Enums TeamRole et TeamPermission pour la gestion fine des rôles et permissions
  - Concerns réutilisables : HasTeams, GeneratesUniqueTeamSlugs, BelongsToTeam
  - Contracts pour une architecture extensible : HasTeamMembership, TeamPermissionContract, TeamRoleContract
  - Support des rôles personnalisables via configuration filateams
  - Relations many-to-many optimisées entre Users et Teams via TeamMember (Pivot)
  - Factory patterns pour Team et TeamInvitation facilitant les tests
  - Livewire components : InvitationsManager et MembersTable pour UI interactive

### CI/CD & DevOps
- **GitHub Actions Workflows** : Intégration et déploiement continus automatisés
  - Workflow CI complet avec tests sur PHP 8.3 et 8.4
  - Tests automatisés avec base de données SQLite et MySQL
  - Vérifications de qualité de code (Laravel Pint, PHPStan)
  - Validation du build frontend (Vite + Tailwind CSS)
  - Workflow CD pour déploiement SSH automatique sur serveur production
  - Gestion automatique des migrations, cache et redémarrage des services
  - Workflow alternatif pour Laravel Forge avec API integration
  - Documentation complète de configuration dans `docs/CI_CD_SETUP.md`
  - Guide détaillé pour la configuration des secrets GitHub
  - Instructions de génération et configuration des clés SSH
  - Support du déploiement manuel via workflow_dispatch
  - Notifications de statut de déploiement
  - Stratégies de cache pour optimiser les temps d'exécution

### Technical
- **Model Refactoring** : Amélioration de l'architecture des modèles
  - TeamMember transformé en Pivot model pour relations many-to-many optimisées
  - User model migré vers le concern local HasTeams au lieu du package externe
  - Casts automatiques pour les rôles d'équipe configurables
  - Route key name personnalisé sur 'slug' pour URLs plus lisibles
  - Factory patterns implémentés pour tous les nouveaux modèles

### Changes
- **GLM Service** : Mise à jour du modèle par défaut
  - Changement du modèle GLM par défaut de 'glm-5.1' à 'glm-4.5-flash'
  - Documentation mise à jour avec les modèles disponibles (glm-4.5-flash, GLM-4.6V-Flash, GLM-4.7-Flash)

## 2026-04-15

### Features
- **GLM AI Service Integration** : Intégration complète de l'API GLM (Z.ai)
  - Service `GlmService` avec trois méthodes principales pour interagir avec l'API GLM
  - Méthode `chatWithHistory()` : Support des conversations avec historique complet (system, user, assistant)
  - Méthode `chatStream()` : Streaming en temps réel avec Server-Sent Events (SSE)
  - Méthode `chatSimple()` : Interface simplifiée pour les requêtes rapides
  - Gestion automatique des erreurs et logging détaillé
  - Support configurable du modèle (défaut: glm-5.1) et de la langue
  - Contrôleur `GlmController` avec endpoints RESTful prêts à l'emploi
  - Routes dédiées : `/glm/chat`, `/glm/chat/history`, `/glm/chat/stream`
  - Documentation complète avec exemples cURL et JavaScript
  - Configuration via variable d'environnement `GLM_API_KEY`

## 2026-04-14

### Features
- **Link Sharing System** : Système complet de partage de liens avec notifications
  - Partage de liens avec utilisateurs inscrits et emails externes
  - Double notification : email + notification in-app (Filament) pour utilisateurs inscrits
  - Tracking complet des partages (envoyé, ouvert, cliqué)
  - Gestion d'expiration des liens partagés (optionnel)
  - Messages personnalisés accompagnant les partages
  - Support de partage multiple (jusqu'à 10 destinataires simultanément)
- **Shared Links Dashboard** : Vues dédiées pour gérer les partages
  - Onglet "Liens envoyés" : historique des partages effectués
  - Onglet "Liens reçus" : liens partagés avec l'utilisateur
  - Statistiques de partage (total envoyé/reçu, activité récente)
  - Filtres par statut et par date
- **AI Description Generator** : Nouvel agent AI pour générer des descriptions enrichies
  - Agent `LinkDescriptionAgent` avec instructions détaillées en français
  - Génération automatique de descriptions structurées basées sur le contenu
  - Suggestions automatiques de tags pertinents (3-5 mots-clés)
  - Détection et suggestion de catégories appropriées
  - Support multi-types de contenu (YouTube, articles, documents, etc.)
  - Template adaptatif pour contenus non-YouTube
- **Tag Management Enhancement** : Gestion améliorée des tags dans les formulaires
  - Champ multi-select pour les tags dans le formulaire de création/édition de liens
  - Création rapide de nouveaux tags directement depuis le formulaire
  - Affichage amélioré des tags dans la vue de détail avec badges colorés
  - Icônes et grille responsive pour une meilleure lisibilité
- **Form Simplification** : Simplification du formulaire LinkForm
  - Suppression du bouton "Generate AI Summary" basé sur les transcriptions YouTube
  - Remplacement par "Generate AI Description" plus polyvalent
  - Interface épurée focalisée sur l'essentiel

### Technical
- **Link Share Architecture** : Architecture robuste pour le partage
  - Modèle `LinkShare` avec relations complètes (sender, recipient, link)
  - Tokens uniques sécurisés pour le tracking (`Str::random(64)`)
  - Action métier `ShareLinkAction` avec gestion d'erreurs complète
  - Mailable `LinkSharedMail` avec queue pour performance
  - Notification `LinkSharedNotification` supportant database, mail et Filament
  - Controller `LinkShareController` pour tracking des clics
  - Route `/share/{token}` avec vérification d'expiration
- **Nouvel Agent AI** : Architecture extensible pour génération de contenu
  - Pattern Promptable pour flexibilité maximale
  - Instructions détaillées avec format de réponse structuré
  - Parsing intelligent de la réponse AI (description, tags, catégorie)
  - Intégration avec Groq et modèle openai/gpt-oss-120b
- **Amélioration UX Tags** : Expérience utilisateur optimisée
  - Select multiple avec recherche et pré-chargement
  - Formulaire inline pour création rapide de tags
  - Relation many-to-many correctement configurée
  - Vue de détail avec RepeatableEntry en grille de 3 colonnes
- **Vue de détail enrichie** : LinkInfolist amélioré
  - Section description ajoutée avec rendu Markdown
  - Tags affichés avec badges colorés et icônes
  - Organisation visuelle clarifiée avec séparateurs
  - Placeholder informatif pour champs vides

## 2026-04-12

### Features