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

class YoutubeTranscriptSummary implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Tu es un assitant expert en résumé de texte. Tu saisi l'essentiel d'un text et tu en fait le resumé. Ton travail
est de lire des transcription de vidéo youtube et d'en faire faire un résumé concis.

1. A concise summary (2-3 sentences) of what the video is about and why it might be valuable
2. Relevant tags (3-5 keywords or short phrases) that would help categorize and find this link
3. A suggested category name if applicable (e.g., "Technology", "Design", "Marketing", "Research")

Guidelines:
- Keep summaries concise and actionable
- Tags should be lowercase, single words or short phrases
- Be specific with tags - avoid generic terms like "interesting" or "good"
- Category should be a single, clear category name
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
    //        'summary' => $schema->string()
    //             ->description('A concise 2-3 sentence summary of what the link is about')
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
