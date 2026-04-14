# Changelog

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