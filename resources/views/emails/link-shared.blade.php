<x-mail::message>
# 🔗 Nouveau lien partagé

Bonjour,

**{{ $sender->name }}** vous a partagé un lien via Linksvault2.

@if($personalMessage)
### 💬 Message personnel :
{{ $personalMessage }}

@endif

---

## 📄 {{ $link->title }}

@if($link->description)
{{ Str::limit($link->description, 200) }}

@endif

**Type de contenu :** {{ $link->content_type?->label() ?? 'Lien web' }}

@if($link->category)
**Catégorie :** {{ $link->category->name }}
@endif

@if($link->tags->count() > 0)
**Tags :** {{ $link->tags->pluck('name')->join(', ') }}
@endif

---

<x-mail::button :url="$shareUrl" color="primary">
👁️ Voir le lien
</x-mail::button>

---

<small>
Ce lien vous a été partagé par **{{ $sender->name }}** via Linksvault2.

@if($share->expires_at)
⏰ Ce lien expirera le {{ $share->expires_at->format('d/m/Y à H:i') }}
@endif

Vous souhaitez gérer vos propres liens ? [Créez un compte sur Linksvault2]({{ config('app.url') }})
</small>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
