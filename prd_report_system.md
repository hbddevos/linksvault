# PRD - Système de Gestion de Rapports

Ce document définit les spécifications fonctionnelles et techniques pour le module de gestion de rapports à intégrer dans l'application Laravel.

## 1. Objectif du Projet
L'objectif est de permettre aux utilisateurs de créer des rapports individuels typés (hebdomadaires, mensuels, semestriels) et de pouvoir les consolider en un rapport global par sélection.

## 2. Fonctionnalités Clés

### 2.1 Gestion des Rapports Individuels
- **Création de rapports** : Saisie du titre, du contenu, de la date de la réunion/période.
- **Typage des rapports** :
    - Hebdomadaire
    - Mensuel
    - Semestriel
- **Édition et Suppression** : Possibilité de modifier ou d'archiver (soft delete) un rapport.
- **Statuts** : Brouillon, Validé.

### 2.2 Génération de Rapports Globaux (Consolidés)
- **Sélection multiple** : Interface permettant de cocher plusieurs rapports individuels existants.
- **Fusion (Merge)** : Logique métier permettant d'assembler les contenus des rapports sélectionnés en un seul document structuré.
- **Aperçu** : Visualisation du rapport global avant finalisation.
- **Export (Optionnel)** : Possibilité d'exporter le rapport global (PDF ou Word).

## 3. Spécifications Techniques

### 3.1 Modèle de Données (Eloquent)
- **Table `reports`** :
    - `id`
    - `type` (Enum: weekly, monthly, semi-annual)
    - `title` (String)
    - `content` (LongText)
    - `meeting_date` (Date)
    - `status` (Enum: draft, published)
    - `user_id` (Foreign key)
    - `created_at` / `updated_at` / `deleted_at` (Soft Deletes)

- **Table `global_reports`** :
    - `id`
    - `title`
    - `summary` (Consolidated content)
    - `created_at` / `updated_at`

- **Table Pivot `global_report_items`** :
    - `global_report_id`
    - `report_id`

### 3.2 Architecture (Laravel context)
- **Modèles** : `Report`, `GlobalReport`
- **Actions** : `CreateReportAction`, `ConsolidateReportsAction`
- **Form Requests** : `StoreReportRequest`, `GenerateGlobalReportRequest`
- **Controllers** : `ReportController`, `GlobalReportController`
- **Services** : `ReportConsolidationService` (pour la logique de fusion)

## 4. Expérience Utilisateur (UX)
- Interface de dashboard listant les derniers rapports.
- Filtre par type pour faciliter la sélection lors de la génération globale.
- Système de "drag and drop" ou de "checkbox" pour ordonner les rapports dans le rapport global.

## 5. Checklist de Conformité (Style Guide)
- [x] Utilisation des **Form Requests** pour la validation.
- [x] Logique métier isolée dans des **Actions/Services**.
- [x] Typer tous les paramètres et retours (PHP 8.x).
- [x] Utilisation des **Enums** pour les types de rapports.
- [x] Eager loading pour éviter N+1 lors de l'affichage des listes.
