# GLM Service - Guide d'Utilisation

## Configuration

1. Ajoutez votre clé API GLM dans le fichier `.env` :
```env
GLM_API_KEY=votre_cle_api_ici
```

2. Le service est automatiquement disponible via l'injection de dépendances Laravel.

## Méthodes Disponibles

### 1. `chatWithHistory()` - Chat avec Historique de Conversation

Cette méthode permet d'envoyer une conversation complète avec historique.

**Exemple d'utilisation :**

```php
use App\Services\GlmService;

class ExampleController extends Controller
{
    public function __construct(
        protected GlmService $glmService
    ) {}

    public function chatWithHistory()
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a professional programming assistant'
            ],
            [
                'role' => 'user',
                'content' => 'What is recursion?'
            ],
            [
                'role' => 'assistant',
                'content' => 'Recursion is a programming technique where a function calls itself to solve problems...'
            ],
            [
                'role' => 'user',
                'content' => 'Can you give me an example of Python recursion?'
            ]
        ];

        $response = $this->glmService->chatWithHistory($messages);
        
        // Extraire la réponse
        $answer = $this->glmService->extractResponse($response);
        
        return response()->json([
            'full_response' => $response,
            'answer' => $answer
        ]);
    }
}
```

### 2. `chatStream()` - Chat en Streaming

Cette méthode permet de recevoir la réponse en temps réel, chunk par chunk.

**Exemple d'utilisation :**

```php
public function chatStream()
{
    $messages = [
        [
            'role' => 'user',
            'content' => 'Write a poem about spring'
        ]
    ];

    $accumulatedResponse = '';

    $this->glmService->chatStream($messages, function ($chunk) use (&$accumulatedResponse) {
        // Traiter chaque chunk reçu
        if (isset($chunk['choices'][0]['delta']['content'])) {
            $content = $chunk['choices'][0]['delta']['content'];
            $accumulatedResponse .= $content;
            
            // Vous pouvez envoyer ce chunk au client en temps réel
            echo $content;
            flush();
        }
    });

    return response()->json([
        'complete_response' => $accumulatedResponse
    ]);
}
```

**Pour les réponses SSE (Server-Sent Events) :**

```php
public function streamEndpoint()
{
    return response()->stream(function () {
        $messages = [
            ['role' => 'user', 'content' => 'Write a poem about spring']
        ];

        echo "data: {\"status\": \"started\"}\n\n";
        flush();

        $this->glmService->chatStream($messages, function ($chunk) {
            if (isset($chunk['choices'][0]['delta']['content'])) {
                $content = $chunk['choices'][0]['delta']['content'];
                echo "data: " . json_encode(['content' => $content]) . "\n\n";
                flush();
            }
        });

        echo "data: {\"status\": \"completed\"}\n\n";
        flush();
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
    ]);
}
```

### 3. `chatSimple()` - Chat Simple

Cette méthode est pour les conversations simples sans historique.

**Exemple d'utilisation :**

```php
public function simpleChat()
{
    $message = 'Hello';
    
    $response = $this->glmService->chatSimple($message);
    
    $answer = $this->glmService->extractResponse($response);
    
    return response()->json([
        'question' => $message,
        'answer' => $answer
    ]);
}
```

## Paramètres Optionnels

Toutes les méthodes acceptent des paramètres optionnels :

- **`$model`** : Spécifier un modèle différent (par défaut : `glm-5.1`)
- **`$language`** : Préférence de langue (par défaut : `en-US,en`)

**Exemple :**

```php
// Utiliser un modèle spécifique
$response = $this->glmService->chatSimple(
    message: 'Bonjour',
    model: 'glm-4',
    language: 'fr-FR,fr'
);

// Chat avec historique et modèle personnalisé
$response = $this->glmService->chatWithHistory(
    messages: $messages,
    model: 'glm-5.1-flash',
    language: 'en-US,en'
);
```

## Gestion des Erreurs

Le service gère automatiquement les erreurs et logue les problèmes. Vous pouvez catcher les exceptions :

```php
try {
    $response = $this->glmService->chatSimple('Hello');
    $answer = $this->glmService->extractResponse($response);
} catch (\Exception $e) {
    Log::error('GLM API error', ['message' => $e->getMessage()]);
    
    return response()->json([
        'error' => 'Failed to get response from AI',
        'message' => $e->getMessage()
    ], 500);
}
```

## Structure de Réponse

La réponse de l'API suit ce format :

```json
{
    "id": "chatcmpl-xxx",
    "object": "chat.completion",
    "created": 1234567890,
    "model": "glm-5.1",
    "choices": [
        {
            "index": 0,
            "message": {
                "role": "assistant",
                "content": "Response content here"
            },
            "finish_reason": "stop"
        }
    ],
    "usage": {
        "prompt_tokens": 10,
        "completion_tokens": 20,
        "total_tokens": 30
    }
}
```

Utilisez la méthode `extractResponse()` pour obtenir facilement le contenu de la réponse.
