# Changelog

## 2026-04-12

### Technical
- Installation des packages filament v5 et Filament-Teams
- Configuration du panel
- Ajout du team_id aux tables : links, categories, tags, exports, google_drives
- Migrations par défaut de tables : links, categories, tags, exports, google_drives

### Features
- Ajout des traductions
- **Team Scoping Global Scope** : Ajout du trait `BelongsToTeam` pour filtrer automatiquement les requêtes par `current_team_id`
  - Filtrage automatique de toutes les requêtes sur les modèles Link, Category, Tag, GoogleDrive
  - Scopes supplémentaires : `withoutTeamScope()`, `forTeam($teamId)`, `allTeams()`
  - Amélioration des traits `AddTeamId` et `AddUserId` avec vérification de l'authentification
- **Modèles créés** : Link, Category, Tag, GoogleDrive, Team, TeamMember
  - Relations complètes entre modèles
  - Application automatique des traits de team scoping
  - Casts et fillables configurés selon les migrations