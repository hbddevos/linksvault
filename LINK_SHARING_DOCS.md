# 📤 Système de Partage de Liens - Documentation

## 🎯 Vue d'ensemble

Le système de partage de liens permet aux utilisateurs de Linksvault2 de partager leurs liens sauvegardés avec d'autres personnes, qu'elles soient inscrites sur la plateforme ou non.

## ✨ Fonctionnalités

### 1. **Partage Multi-Destinataires**
- Partager avec jusqu'à 10 destinataires simultanément
- Support des utilisateurs inscrits (recherche par nom)
- Support des emails externes
- Auto-complétion des informations pour utilisateurs inscrits

### 2. **Notifications Doubles**
Pour les utilisateurs inscrits :
- 📧 **Email** détaillé avec le lien et message personnel
- 🔔 **Notification in-app** dans l'interface Filament

Pour les emails externes :
- 📧 **Email** uniquement avec le lien de partage

### 3. **Tracking Complet**
Chaque partage est tracké avec les statuts suivants :
- `pending` : Créé mais pas encore envoyé
- `sent` : Email envoyé avec succès
- `opened` : Destinataire a ouvert l'email
- `clicked` : Destinataire a cliqué sur le lien

### 4. **Gestion d'Expiration**
- Option pour définir une date d'expiration (1-365 jours)
- Par défaut : pas d'expiration
- Lien expiré affiche un message d'erreur approprié

### 5. **Messages Personnels**
- Ajouter un message personnalisé (max 500 caractères)
- Affiché dans l'email et la notification

## 🚀 Comment Utiliser

### Partager un Lien

1. **Ouvrir un lien** dans Linksvault2
2. **Cliquer sur "Partager"** dans la section Actions
3. **Remplir le formulaire** :
   - Ajouter les destinataires (email ou sélection utilisateur)
   - Optionnel : Message personnel
   - Optionnel : Date d'expiration
4. **Cliquer sur "Envoyer"**

### Voir les Liens Partagés

#### Liens Envoyés
```php
// Récupérer via l'action
$action = app(GetSharedLinksAction::class);
$sentLinks = $action->getSentLinks(auth()->id());
```

#### Liens Reçus
```php
$receivedLinks = $action->getReceivedLinks(auth()->id());
```

### Statistiques
```php
$stats = $action->getStats(auth()->id());
// Retourne :
// - total_sent
// - total_received
// - sent_this_week
// - received_this_week
// - most_shared_link
// - recent_activity
```

## 🏗️ Architecture Technique

### Modèles

#### LinkShare
```php
- id (UUID)
- link_id (FK)
- sender_user_id (FK)
- recipient_user_id (FK, nullable)
- recipient_email
- recipient_name (nullable)
- personal_message (text, nullable)
- token (string, unique, 64 chars)
- status (enum: pending, sent, opened, clicked)
- expires_at (datetime, nullable)
- sent_at, opened_at, clicked_at (datetime, nullable)
```

### Relations

**User :**
```php
$user->sharedLinks()    // Liens que j'ai partagés
$user->receivedLinks()  // Liens reçus par moi
```

**Link :**
```php
$link->shares()  // Tous les partages de ce lien
```

**LinkShare :**
```php
$share->link      // Le lien partagé
$share->sender    // L'utilisateur qui a partagé
$share->recipient // L'utilisateur destinataire (si inscrit)
```

### Services & Actions

#### ShareLinkAction
**Responsabilité** : Logique métier principale du partage

```php
$result = app(ShareLinkAction::class)->execute(
    link: $link,
    sender: $user,
    recipients: [
        ['email' => 'john@example.com', 'user_id' => 5, 'name' => 'John'],
        ['email' => 'external@gmail.com', 'user_id' => null, 'name' => 'External']
    ],
    personalMessage: 'Regarde ce lien intéressant !',
    expiresInDays: 7
);
```

**Retour :**
```php
[
    'success' => true/false,
    'shares' => [/* array of LinkShare models */],
    'errors' => [/* array of errors */]
]
```

#### GetSharedLinksAction
**Responsabilité** : Récupération et filtrage des partages

```php
$action = app(GetSharedLinksAction::class);

// Liens envoyés avec filtres
$sent = $action->getSentLinks($userId, [
    'status' => 'clicked',
    'date_from' => '2026-04-01',
    'date_to' => '2026-04-14'
]);

// Liens reçus
$received = $action->getReceivedLinks($userId);

// Statistiques
$stats = $action->getStats($userId);
```

### Notifications

#### LinkSharedMail (Mailable)
- Queue : ✅ Oui (ShouldQueue)
- Template : Markdown (`emails.link-shared`)
- Contenu : Titre, description, type, catégorie, tags, message personnel

#### LinkSharedNotification
- Canaux : `database`, `mail`
- Filament : ✅ Support natif avec actions
- Persistence : Oui (reste jusqu'à lecture)

### Routes

```php
GET /share/{token}  →  LinkShareController@redirect
```

**Fonctionnement :**
1. Lookup du token
2. Vérification expiration
3. Marquer comme ouvert/cliqué
4. Incrémenter visit_count du lien
5. Rediriger vers URL originale

## 🔐 Sécurité

### Validation
- ✅ Emails validés avec `filter_var(FILTER_VALIDATE_EMAIL)`
- ✅ Tokens uniques générés avec `Str::random(64)`
- ✅ Vérification d'expiration avant chaque accès
- ✅ Rate limiting recommandé en production

### Permissions
- ✅ Utilisateurs ne voient que LEURS partages
- ✅ Impossible de voir les partages entre autres utilisateurs
- ✅ Liens expirés retournent erreur 410

### Protection Anti-Spam
Recommandations :
```php
// Dans un middleware ou FormRequest
public function rules(): array
{
    return [
        'recipients.*.email' => ['required', 'email', 'max:255'],
        'personal_message' => ['nullable', 'string', 'max:500'],
    ];
}

// Rate limiting dans RouteServiceProvider
RateLimiter::for('link-sharing', function (Request $request) {
    return Limit::perHour(50)->by($request->user()->id);
});
```

## 📊 Exemples d'Utilisation

### Exemple 1 : Partage Simple
```php
use App\Actions\LinkShareActions\ShareLinkAction;

$link = Link::find(1);
$user = auth()->user();

$action = app(ShareLinkAction::class);
$result = $action->execute(
    link: $link,
    sender: $user,
    recipients: [
        ['email' => 'colleague@company.com']
    ],
    personalMessage: 'À lire absolument !'
);

if ($result['success']) {
    echo "Partagé avec succès !";
}
```

### Exemple 2 : Partage Multiple avec Expiration
```php
$result = $action->execute(
    link: $link,
    sender: $user,
    recipients: [
        ['email' => 'alice@example.com', 'name' => 'Alice'],
        ['email' => 'bob@example.com', 'name' => 'Bob'],
        ['email' => 'charlie@example.com', 'name' => 'Charlie'],
    ],
    personalMessage: 'Ressources pour le projet',
    expiresInDays: 14
);
```

### Exemple 3 : Récupération avec Filtres
```php
use App\Actions\LinkShareActions\GetSharedLinksAction;

$action = app(GetSharedLinksAction::class);

// Liens reçus cette semaine, non lus
$unreadThisWeek = $action->getReceivedLinks(auth()->id(), [
    'status' => 'sent',
    'date_from' => now()->startOfWeek(),
]);

// Statistiques complètes
$stats = $action->getStats(auth()->id());
echo "Vous avez partagé {$stats['total_sent']} liens";
echo "Votre lien le plus partagé : {$stats['most_shared_link']['link']->title}";
```

## 🎨 Personnalisation

### Modifier le Template Email
Éditer : `resources/views/emails/link-shared.blade.php`

### Changer la Durée d'Expiration par Défaut
Dans `ShareLinkModalAction.php` :
```php
TextInput::make('expires_in_days')
    ->default(14)  // Au lieu de 7
```

### Ajouter des Canaux de Notification
Dans `LinkSharedNotification.php` :
```php
public function via(object $notifiable): array
{
    return ['database', 'mail', 'slack'];  // Ajouter Slack par exemple
}
```

## 🐛 Dépannage

### Les emails ne s'envoient pas
1. Vérifier la configuration mail dans `.env`
2. Tester avec Mailtrap en développement
3. Vérifier les logs : `storage/logs/laravel.log`
4. S'assurer que le queue worker tourne : `php artisan queue:work`

### La notification Filament n'apparaît pas
1. Vérifier que le destinataire est bien un utilisateur inscrit
2. Contrôler la table `notifications` dans la base
3. Rafraîchir la page Filament

### Le tracking ne fonctionne pas
1. Vérifier que la route `/share/{token}` est accessible
2. Contrôler les logs d'erreurs
3. Tester manuellement : `curl http://localhost/share/TOKEN`

## 📈 Améliorations Futures

- [ ] Historique complet des partages dans une page dédiée
- [ ] Graphiques de statistiques (Chart.js)
- [ ] Partage en masse depuis la liste des liens
- [ ] Templates de messages prédéfinis
- [ ] Suggestions de destinataires basées sur les tags
- [ ] Intégration Slack/Teams
- [ ] QR codes pour partage mobile
- [ ] Analytics avancés (géolocalisation, device, etc.)

## 📝 Notes Techniques

### Performance
- Emails mis en queue automatiquement (ShouldQueue)
- Index database sur `sender_user_id`, `recipient_user_id`, `token`
- Eager loading des relations pour éviter N+1 queries

### Scalabilité
- Supporte jusqu'à 10 destinataires par partage
- Tokens UUID uniques (collision quasi-impossible)
- Pagination recommandée pour >1000 partages

### Monitoring
Logs importants dans `storage/logs/laravel.log` :
- Erreurs d'envoi d'email
- Tentatives d'accès à partages expirés
- Exceptions lors du tracking

---

**Dernière mise à jour** : 14 Avril 2026  
**Version** : 1.0.0
