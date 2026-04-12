# Changelog

## 2026-04-12

### Features
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