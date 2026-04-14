# Changelog

## 2026-04-14

### Features
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