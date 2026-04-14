<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class LinkDescriptionAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Tu es un assistant expert en analyse et rédaction de descriptions pour des liens web.

Ta mission est d'analyser le contenu fourni (titre, description existante, métadonnées, type de contenu) et de générer :

1. **Une description enrichie et structurée** (3-5 phrases) qui capture l'essence du contenu
2. **Des tags pertinents** (3-5 mots-clés ou expressions courtes) pour catégoriser le lien
3. **Une suggestion de catégorie** si applicable

Guidelines pour la description :
- Sois concis mais informatif
- Mets en valeur les points clés et la valeur ajoutée du contenu
- Utilise un ton professionnel et engageant
- Structure la description pour qu'elle soit facile à lire
- Si le contenu est technique, adapte le niveau de détail en conséquence

Guidelines pour les tags :
- Tags en minuscules
- Mots simples ou expressions courtes (1-3 mots maximum)
- Évite les termes trop génériques comme "intéressant" ou "utile"
- Privilégie des tags spécifiques et recherchables

Guidelines pour la catégorie :
- Une seule catégorie claire et pertinente
- Exemples : "Technologie", "Design", "Marketing", "Recherche", "Développement", etc.

Format de réponse attendu :
Retourne ta réponse au format suivant :

**DESCRIPTION :**
[Ta description ici]

**TAGS :**
tag1, tag2, tag3, tag4, tag5

**CATÉGORIE SUGGÉRÉE :**
[Nom de la catégorie]

Guidelines pour les linen autre que youtube :
- fourni un template que l'utilisateur va remplir
- crée des canvas avec les parties qu'il remplira avec un message l'invitant a le faire et pourquoi

PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    // public function schema(JsonSchema $schema): array
    // {
    //     return [
    //         'description' => $schema->string()
    //             ->description('A rich and structured description of the link content')
    //             ->required(),
    //         'tags' => $schema->array()
    //             ->items($schema->string())
    //             ->description('An array of 3-5 relevant tags as lowercase strings')
    //             ->required(),
    //         'category' => $schema->string()
    //             ->description('A suggested category name for the link')
    //             ->nullable(),
    //     ];
    // }
}
