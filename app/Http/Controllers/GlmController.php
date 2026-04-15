<?php

namespace App\Http\Controllers;

use App\Services\GlmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlmController extends Controller
{
    public function __construct(
        protected GlmService $glmService
    ) {}

    /**
     * Example: Chat with conversation history
     */
    public function chatWithHistory(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'model' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        try {
            $response = $this->glmService->chatWithHistory(
                messages: $validated['messages'],
                model: $validated['model'] ?? null,
                language: $validated['language'] ?? 'en-US,en'
            );

            return response()->json([
                'success' => true,
                'data' => $response,
                'answer' => $this->glmService->extractResponse($response),
            ]);
        } catch (\Exception $e) {
            Log::error('GLM chat with history failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get response from AI',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Example: Streaming chat response
     */
    public function chatStream(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'model' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        return response()->stream(function () use ($validated) {
            try {
                $this->glmService->chatStream(
                    messages: $validated['messages'],
                    callback: function ($chunk) {
                        if (isset($chunk['choices'][0]['delta']['content'])) {
                            $content = $chunk['choices'][0]['delta']['content'];
                            echo "data: " . json_encode(['content' => $content]) . "\n\n";
                            flush();
                        }
                    },
                    model: $validated['model'] ?? null,
                    language: $validated['language'] ?? 'en-US,en'
                );

                echo "data: " . json_encode(['status' => 'completed']) . "\n\n";
                flush();
            } catch (\Exception $e) {
                echo "data: " . json_encode([
                    'error' => 'Streaming failed',
                    'message' => $e->getMessage(),
                ]) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Example: Simple chat
     */
    public function simpleChat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'model' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        try {
            $response = $this->glmService->chatSimple(
                message: $validated['message'],
                model: $validated['model'] ?? null,
                language: $validated['language'] ?? 'en-US,en'
            );

            return response()->json([
                'success' => true,
                'data' => $response,
                'answer' => $this->glmService->extractResponse($response),
            ]);
        } catch (\Exception $e) {
            Log::error('GLM simple chat failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get response from AI',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
