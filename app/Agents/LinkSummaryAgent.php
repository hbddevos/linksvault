<?php

namespace App\Agents;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent as AgentContract;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebFetch;
use Stringable;

class LinkSummaryAgent implements AgentContract
{
    use Promptable, RemembersConversations;

    public function __construct(
    ) {}

    /**
     * The instructions/prompt template for the agent.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a link analysis assistant for a personal link vault application. Your job is to analyze saved links and provide:

'1. A concise summary (2-3 sentences) of what the link is about and why it might be valuable
2. Relevant tags (3-5 keywords or short phrases) that would help categorize and find this link
3. A suggested category name if applicable (e.g., "Technology", "Design", "Marketing", "Research")

Guidelines:
- Keep summaries concise and actionable
- Tags should be lowercase, single words or short phrases
- Be specific with tags - avoid generic terms like "interesting" or "good"
- If the link is a YouTube video, mention the channel and topic
- If the link is a PDF or document, note the key topics covered
- If the link is an article, summarize the main argument or findings
- Category should be a single, clear category name'
PROMPT;
    }

    /**
     * Define the structured output schema.
     *
     * @return array<string, mixed>
     */
    // public function schema(JsonSchema $schema): array
    // {
    //     return [
    //         'summary' => $schema->string()
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

    /**
     * Get the conversation identifier for this agent.
     */
    // public function conversationKey(): string
    // {
    //     return "link_summary_{$this->user->id}";
    // }

    /**
     * Get the agent name for conversation tracking.
     */
    public function agentName(): string
    {
        return 'LinkSummaryAgent';
    }

    public function tools(): iterable
    {
        return [
            (new WebFetch)->allow([
                'youtube.com',
                'youtu.be',
            ]),
        ];
    }
}
