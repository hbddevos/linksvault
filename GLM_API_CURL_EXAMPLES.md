# GLM API - Exemples de Test avec cURL

## Prérequis

Assurez-vous d'avoir configuré votre clé API dans le fichier `.env` :
```env
GLM_API_KEY=votre_cle_api_ici
```

## Endpoints Disponibles

### 1. Chat Simple

**Endpoint:** `POST /glm/chat`

**Exemple cURL :**
```bash
curl --location 'http://localhost:8000/glm/chat' \
--header 'Content-Type: application/json' \
--data '{
    "message": "Hello",
    "model": "glm-5.1",
    "language": "en-US,en"
}'
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "id": "chatcmpl-xxx",
        "object": "chat.completion",
        "created": 1234567890,
        "model": "glm-5.1",
        "choices": [...],
        "usage": {...}
    },
    "answer": "Hello! How can I help you today?"
}
```

---

### 2. Chat avec Historique

**Endpoint:** `POST /glm/chat/history`

**Exemple cURL :**
```bash
curl --location 'http://localhost:8000/glm/chat/history' \
--header 'Content-Type: application/json' \
--data '{
    "messages": [
        {
            "role": "system",
            "content": "You are a professional programming assistant"
        },
        {
            "role": "user",
            "content": "What is recursion?"
        },
        {
            "role": "assistant",
            "content": "Recursion is a programming technique where a function calls itself to solve problems by breaking them down into smaller, similar subproblems."
        },
        {
            "role": "user",
            "content": "Can you give me an example of Python recursion?"
        }
    ],
    "model": "glm-5.1",
    "language": "en-US,en"
}'
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "id": "chatcmpl-xxx",
        "object": "chat.completion",
        "created": 1234567890,
        "model": "glm-5.1",
        "choices": [...],
        "usage": {...}
    },
    "answer": "Here's a simple example of recursion in Python:\n\n```python\ndef factorial(n):\n    if n <= 1:\n        return 1\n    return n * factorial(n - 1)\n\nprint(factorial(5))  # Output: 120\n```"
}
```

---

### 3. Chat en Streaming

**Endpoint:** `POST /glm/chat/stream`

**Exemple cURL :**
```bash
curl --location 'http://localhost:8000/glm/chat/stream' \
--header 'Content-Type: application/json' \
--header 'Accept: text/event-stream' \
--data '{
    "messages": [
        {
            "role": "user",
            "content": "Write a poem about spring"
        }
    ],
    "model": "glm-5.1",
    "language": "en-US,en"
}'
```

**Réponse (Server-Sent Events) :**
```
data: {"content":"Spring"}

data: {"content":" arrives"}

data: {"content":" with"}

data: {"content":" gentle"}

data: {"content":" breeze"}

...

data: {"status":"completed"}
```

**Pour voir le streaming en temps réel dans le terminal :**
```bash
curl -N --location 'http://localhost:8000/glm/chat/stream' \
--header 'Content-Type: application/json' \
--data '{
    "messages": [
        {
            "role": "user",
            "content": "Write a short story"
        }
    ]
}'
```

L'option `-N` désactive le buffering pour voir les chunks en temps réel.

---

## Utilisation avec JavaScript (Fetch API)

### Chat Simple
```javascript
const response = await fetch('http://localhost:8000/glm/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        message: 'Hello',
        model: 'glm-5.1',
        language: 'en-US,en'
    })
});

const data = await response.json();
console.log(data.answer);
```

### Chat avec Historique
```javascript
const response = await fetch('http://localhost:8000/glm/chat/history', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        messages: [
            { role: 'user', content: 'What is AI?' },
            { role: 'assistant', content: 'AI is artificial intelligence...' },
            { role: 'user', content: 'Give me examples' }
        ],
        model: 'glm-5.1'
    })
});

const data = await response.json();
console.log(data.answer);
```

### Chat en Streaming
```javascript
const response = await fetch('http://localhost:8000/glm/chat/stream', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        messages: [
            { role: 'user', content: 'Write a poem' }
        ]
    })
});

const reader = response.body.getReader();
const decoder = new TextDecoder();

while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    
    const chunk = decoder.decode(value);
    const lines = chunk.split('\n');
    
    for (const line of lines) {
        if (line.startsWith('data: ')) {
            const data = JSON.parse(line.slice(6));
            if (data.content) {
                console.log(data.content); // Affiche chaque chunk
            }
        }
    }
}
```

---

## Codes d'Erreur

| Code | Description |
|------|-------------|
| 200  | Succès |
| 400  | Requête invalide (validation échouée) |
| 401  | Clé API manquante ou invalide |
| 500  | Erreur serveur ou API GLM indisponible |

---

## Notes Importantes

1. **Rate Limiting**: L'API GLM peut avoir des limites de taux. Vérifiez votre plan.
2. **Timeout**: Les requêtes longues peuvent timeout. Ajustez si nécessaire.
3. **Streaming**: Pour le streaming, utilisez l'en-tête `Accept: text/event-stream`.
4. **Modèles**: Le modèle par défaut est `glm-5.1`. Vous pouvez spécifier d'autres modèles supportés.
5. **Langue**: Le paramètre `language` influence la langue de la réponse (format: `fr-FR,fr` ou `en-US,en`).

---

## Dépannage

### Erreur: "GLM API key is not configured"
- Vérifiez que `GLM_API_KEY` est défini dans `.env`
- Redémarrez le serveur Laravel après modification

### Erreur: "Failed to get response from AI"
- Vérifiez votre connexion internet
- Vérifiez que la clé API est valide
- Consultez les logs Laravel: `storage/logs/laravel.log`

### Streaming ne fonctionne pas
- Assurez-vous d'utiliser l'option `-N` avec cURL
- Vérifiez que le buffering est désactivé (`X-Accel-Buffering: no`)
- Consultez la documentation SSE pour votre client
