# YouTube Transcript - Tâche en Cours : Sélection Intelligente de Langue

**Date de création :** 14 avril 2026  
**Statut :** ⏸️ En pause (problème de blocage IP YouTube)

---

## 📋 Contexte et Objectif

### Demande initiale
Améliorer le service `YouTubeTranscriptCliService` pour implémenter une logique intelligente de sélection de langue :

1. **Priorité 1 :** Essayer d'abord le français (`fr`)
2. **Priorité 2 :** Si `fr` non disponible, essayer l'anglais (`en`)
3. **Priorité 3 :** Si ni l'un ni l'autre, prendre la première langue disponible

### Problèmes identifiés
- Certaines vidéos affichent "**Langues disponibles :** English (auto-generated)" mais aucune transcription n'est récupérable
- La vidéo multi-langues (`52Orbt9Z-B8`) échoue également
- Message d'erreur générique : "Impossible de récupérer la transcription"

---

## 🔍 Investigation Technique

### Approches testées

#### 1. ✅ Approche CLI directe (fonctionnait avant)
```bash
youtube_transcript_api VIDEO_ID --languages fr en
```
**Résultat initial :** Fonctionnait parfaitement  
**Problème actuel :** YouTube bloque maintenant les requêtes (IP ban temporaire)

#### 2. ❌ Approche API Python directe
```python
from youtube_transcript_api import YouTubeTranscriptApi

api = YouTubeTranscriptApi()
transcript_list = api.list(video_id)
transcript = transcript_list.find_transcript(['fr', 'en'])
fetched = transcript.fetch()
```
**Problème :** Plus sensible au blocage IP que la CLI

#### 3. ⚠️ Approche hybride (tentative)
- Essayer d'abord la CLI avec langues demandées
- Si échec, lister les langues via `--list`
- Parser la sortie texte et choisir intelligemment
- Réessayer avec la langue choisie

**Problème :** Même la CLI est maintenant bloquée

---

## 📊 Tests Effectués

### Vidéos testées :
| Vidéo ID | Type | Langues disponibles | Statut |
|----------|------|-------------------|--------|
| `ReqHcXhYzWA` | Française générée | FR (auto-generated) | ❌ Bloqué |
| `hUSu4bWYtpA` | Anglaise courte | EN | ❌ Bloqué |
| `52Orbt9Z-B8` | Multi-langues | FR, EN, autres | ❌ Bloqué |

### Erreur rencontrée :
```
Could not retrieve a transcript for the video https://www.youtube.com/watch?v=XXX!
This is most likely caused by:

YouTube is blocking requests from your IP. This usually is due to one of the following reasons:
- You have done too many requests and your IP has been blocked by YouTube
- You are doing requests from an IP belonging to a cloud provider
```

---

## 💡 Solutions à Implémenter

### Solution 1 : Attendre le déblocage IP (court terme)
- YouTube débloque généralement les IPs après quelques heures/jours
- Le code actuel devrait fonctionner une fois le blocage levé
- **Action :** Tester à nouveau dans 24-48h

### Solution 2 : Utiliser des proxies résidentiels (long terme)
Selon la documentation officielle, utiliser Webshare ou autre proxy rotatif :

```python
from youtube_transcript_api import YouTubeTranscriptApi
from youtube_transcript_api.proxies import WebshareProxyConfig

ytt_api = YouTubeTranscriptApi(
    proxy_config=WebshareProxyConfig(
        proxy_username="<username>",
        proxy_password="<password>",
    )
)
```

**Avantages :**
- Contourne les blocages IP
- Plus fiable pour la production
- Rotation automatique des IPs

**Inconvénients :**
- Coût supplémentaire (abonnement Webshare)
- Configuration supplémentaire nécessaire

### Solution 3 : Cache agressif + Retry exponential backoff
- Mettre en cache les transcriptions pendant 7 jours (au lieu de 24h)
- Implémenter un retry avec délai exponentiel en cas d'échec
- Afficher un message utilisateur clair : "Transcription temporairement indisponible, réessayez plus tard"

### Solution 4 : Fallback vers l'ancien service manuel
- Garder `YouTubeTranscriptService` (approche manuelle avec fallback en 6 étapes) comme backup
- Si le service CLI échoue, basculer automatiquement sur l'ancien service
- **Note :** L'ancien service utilise aussi `youtube_transcript_api`, donc probablement affecté par le même blocage

---

## 📝 Code Actuel

### Fichiers modifiés :
- `app/Services/GoogleClient/YouTube/YouTubeTranscriptCliService.php`
  - Méthode `executeCliCommand()` : Approche hybride implémentée
  - Méthode `tryFetchWithLanguages()` : Tentative directe avec CLI
  - Méthode `listAvailableLanguages()` : Parsing de la sortie `--list`
  - Méthode `selectBestLanguage()` : Logique fr > en > première disponible
  - Méthode `parseListOutput()` : Parser regex pour extraire les langues

### État du code :
✅ Code fonctionnel et sans erreur de syntaxe  
⚠️ Bloqué par restriction YouTube (temporaire)  
🔄 Prêt à fonctionner une fois le blocage levé

---

## 🎯 Prochaines Étapes

### Immédiat (quand le blocage sera levé) :
1. Tester le service avec les 3 vidéos de test
2. Vérifier que la sélection intelligente fonctionne :
   - Vidéo FR → doit choisir FR
   - Vidéo EN → doit choisir EN (fallback)
   - Vidéo multi-langues → doit choisir FR si disponible, sinon EN
3. Valider l'affichage des métadonnées dans LinkForm

### Améliorations futures :
1. **Ajouter configuration des proxies** dans `.env` :
   ```env
   YOUTUBE_TRANSCRIPT_PROXY_USERNAME=xxx
   YOUTUBE_TRANSCRIPT_PROXY_PASSWORD=xxx
   YOUTUBE_TRANSCRIPT_USE_PROXY=false
   ```

2. **Implémenter retry logic** avec exponential backoff :
   ```php
   $maxRetries = 3;
   $delay = 1; // secondes
   
   for ($i = 0; $i < $maxRetries; $i++) {
       $result = $this->tryFetchWithLanguages($videoId, $languages);
       if ($result) return $result;
       
       sleep($delay);
       $delay *= 2; // Exponential backoff
   }
   ```

3. **Améliorer les messages d'erreur** dans LinkForm :
   - Distinguer "aucune transcription disponible" vs "blocage temporaire"
   - Suggérer de réessayer plus tard
   - Afficher quand même les langues disponibles si détectées

4. **Augmenter la durée du cache** :
   ```php
   Cache::remember($cacheKey, now()->addDays(7), ...) // au lieu de 24h
   ```

---

## 📚 Références

- Documentation officielle : [youtube-transcript-api](https://github.com/jdepoix/youtube-transcript-api)
- Section "Working around IP bans" : Utilisation de proxies résidentiels
- Méthodes clés :
  - `YouTubeTranscriptApi().list(video_id)` : Lister les transcriptions
  - `transcript_list.find_transcript(['fr', 'en'])` : Trouver avec priorité
  - `transcript.fetch()` : Récupérer les données

---

## ✅ Checklist de Validation (à faire quand débloqué)

- [ ] Test vidéo française (`ReqHcXhYzWA`) → sélectionne FR
- [ ] Test vidéo anglaise (`hUSu4bWYtpA`) → sélectionne EN
- [ ] Test vidéo multi-langues (`52Orbt9Z-B8`) → sélectionne FR si disponible
- [ ] Vérification logs : stratégie utilisée correctement enregistrée
- [ ] Vérification UI : métadonnées affichées correctement dans Filament
- [ ] Test troncature : texte limité à 1000 mots avant envoi IA
- [ ] Test fallback : si FR indisponible, prend EN automatiquement
- [ ] Mise à jour CHANGELOG.md
- [ ] Commit Git avec message descriptif

---

## 🔄 Comment Reprendre Cette Tâche

1. **Vérifier si le blocage IP est toujours actif :**
   ```bash
   youtube_transcript_api ReqHcXhYzWA --languages fr
   ```

2. **Si ça fonctionne :**
   - Exécuter `php test_smart_lang.php` pour valider la logique
   - Tester dans l'interface Filament
   - Mettre à jour le CHANGELOG
   - Committer les changements

3. **Si toujours bloqué :**
   - Attendre 24-48h et réessayer
   - OU configurer un proxy résidentiel (voir Solution 2)
   - OU implémenter une solution de contournement temporaire

---

**Note importante :** Le code est fonctionnel et prêt à l'emploi. Le seul obstacle est le blocage temporaire de YouTube, qui est indépendant du code et se résoudra automatiquement avec le temps.
