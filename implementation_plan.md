# Plan d'implémentation - Système de Gestion de Rapports

Ce plan détaille les étapes pour implémenter la gestion des rapports et la consolidation globale.

## User Review Required

> [!IMPORTANT]
> Nous utiliserons les **Enums** PHP 8.1 pour les types de rapports (Hebdomadaire, Mensuel, Semestriel).
> Confirmez-vous que le format de consolidation doit être un simple assemblage de textes ou un document .docx généré ?

## Proposed Changes

### Database & Models

#### [NEW] [ReportType.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Enums/ReportType.php)
- Enum pour `weekly`, `monthly`, `semi_annual`.

#### [NEW] [ReportStatus.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Enums/ReportStatus.php)
- Enum pour `draft`, `published`.

#### [NEW] [2024_xx_xx_create_reports_table.php](file:///c:/Users/HP/Herd/ecommerce_learn/database/migrations/2026_04_14_000001_create_reports_table.php)
- Table pour les rapports individuels.

#### [NEW] [2024_xx_xx_create_global_reports_table.php](file:///c:/Users/HP/Herd/ecommerce_learn/database/migrations/2026_04_14_000002_create_global_reports_table.php)
- Table pour les rapports consolidés et table pivot.

#### [NEW] [Report.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Models/Report.php)
- Modèle Eloquent avec relations et scopes.

#### [NEW] [GlobalReport.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Models/GlobalReport.php)
- Modèle Eloquent pour les consolidations.

---

### Business Logic (Actions & Services)

#### [NEW] [StoreReportAction.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Actions/Reports/StoreReportAction.php)
- Action pour valider et enregistrer un rapport.

#### [NEW] [ConsolidateReportsAction.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Actions/Reports/ConsolidateReportsAction.php)
- Action pour assembler les rapports sélectionnés.

#### [NEW] [ReportConsolidationService.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Services/ReportConsolidationService.php)
- Service gérant la logique de fusion des contenus.

---

### Web Layer (Controllers & Requests)

#### [NEW] [ReportRequest.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Http/Requests/ReportRequest.php)
- Form Request pour la validation des rapports.

#### [NEW] [ReportController.php](file:///c:/Users/HP/Herd/ecommerce_learn/app/Http/Controllers/ReportController.php)
- Contrôleur CRUD pour les rapports.

---

## Open Questions

- Souhaitez-vous une interface d'administration spécifique (ex: Filament) ou des vues Blade simples ?
- Le rapport global doit-il être exportable en PDF/Word dès maintenant ?

## Verification Plan

### Automated Tests
- `php artisan test` : Tests unitaires sur l'Action de consolidation.
- Tests Pest pour vérifier les changements de statut.

### Manual Verification
- Création de 3 rapports (hebdomadaires).
- Sélection des 3 et vérification de la génération du rapport global via l'UI.
