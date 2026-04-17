<?php

declare(strict_types=1);

namespace App\Services\YouTube;

use App\DTOs\YouTube\YouTubeTranscription;
use App\Exceptions\YouTubeTranscriptionException;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Service for fetching and parsing YouTube video transcriptions.
 *
 * This service executes the youtube-transcript-api Python script and parses
 * the returned JSON output into structured PHP DTOs.
 */
class YouTubeTranscriptionService
{
    private string $pythonPath;
    private string $scriptPath;
    private array $defaultLanguages;

    public function __construct(
        ?string $pythonPath = null,
        ?string $scriptPath = null,
        ?array $defaultLanguages = null,
    ) {
        $this->pythonPath = $pythonPath ?? config('services.youtube_transcription.python_path', 'python3');
        $this->scriptPath = $scriptPath ?? config('services.youtube_transcription.script_path', base_path('storage/app/scripts/get_transcript.py'));
        $this->defaultLanguages = $defaultLanguages ?? config('services.youtube_transcription.languages', ['en', 'fr', 'es', 'de']);
    }

    /**
     * Fetch transcription for a video.
     *
     * @param string $videoId The YouTube video ID
     * @param array<string>|null $languages Preferred languages (ordered by priority)
     *
     * @throws YouTubeTranscriptionException
     */
    public function getTranscription(string $videoId, ?array $languages = null): YouTubeTranscription
    {
        $languages = $languages ?? $this->defaultLanguages;

        $this->validateVideoId($videoId);
        $this->validateLanguages($languages);

        $command = $this->buildCommand($videoId, $languages);
        $result = $this->executeScript($command);

        return $this->parseResponse($result, $videoId);
    }

    /**
     * Fetch transcription synchronously (same as getTranscription, alias for clarity).
     *
     * @throws YouTubeTranscriptionException
     */
    public function fetch(string $videoId, ?array $languages = null): YouTubeTranscription
    {
        return $this->getTranscription($videoId, $languages);
    }

    /**
     * Fetch transcription asynchronously and return a promise/coroutine.
     *
     * Note: In a real implementation, you might use ReactPHP or Swoole for async execution.
     * This is a simplified synchronous version that can be wrapped in a job queue.
     *
     * @throws YouTubeTranscriptionException
     */
    public function fetchAsync(string $videoId, ?array $languages = null): YouTubeTranscription
    {
        // For now, this is identical to sync version
        // In production, you would dispatch a job and return a pending result
        return $this->getTranscription($videoId, $languages);
    }

    /**
     * Check if a transcript is available for a video.
     *
     * @param string $videoId The YouTube video ID
     *
     * @throws YouTubeTranscriptionException
     */
    public function isAvailable(string $videoId): bool
    {
        try {
            $this->getTranscription($videoId);

            return true;
        } catch (YouTubeTranscriptionException $e) {
            if ($e->getCode() === YouTubeTranscriptionException::ERROR_TRANSCRIPT_UNAVAILABLE) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Try multiple languages and return the first available transcription.
     *
     * @param string $videoId The YouTube video ID
     * @param array<string>|null $languages Languages to try (null = use defaults)
     *
     * @throws YouTubeTranscriptionException if no transcript is available in any language
     */
    public function getTranscriptionMultiLanguage(string $videoId, ?array $languages = null): YouTubeTranscription
    {
        $languages = $languages ?? $this->defaultLanguages;

        $lastException = null;

        foreach ($languages as $language) {
            try {
                return $this->getTranscription($videoId, [$language]);
            } catch (YouTubeTranscriptionException $e) {
                $lastException = $e;

                // Continue to next language
                continue;
            }
        }

        // All languages failed, throw the last exception
        throw $lastException ?? YouTubeTranscriptionException::transcriptUnavailable($videoId);
    }

    /**
     * Validate YouTube video ID format.
     *
     * @throws \InvalidArgumentException
     */
    private function validateVideoId(string $videoId): void
    {
        // YouTube video IDs are typically 11 characters
        // They can contain letters, numbers, underscores, and hyphens
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            throw new \InvalidArgumentException(
                "Invalid YouTube video ID format: '{$videoId}'. Expected 11-character alphanumeric string."
            );
        }
    }

    /**
     * Validate language codes.
     *
     * @param array<string> $languages
     *
     * @throws \InvalidArgumentException
     */
    private function validateLanguages(array $languages): void
    {
        if (empty($languages)) {
            throw new \InvalidArgumentException('At least one language code must be provided.');
        }

        foreach ($languages as $language) {
            if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $language)) {
                throw new \InvalidArgumentException(
                    "Invalid language code format: '{$language}'. Expected ISO 639-1 code (e.g., 'en', 'fr-FR')."
                );
            }
        }
    }

    /**
     * Build the command to execute.
     */
    private function buildCommand(string $videoId, array $languages): string
    {
        $languagesString = implode(' ', $languages);

        return sprintf(
            '%s %s %s --languages %s',
            escapeshellcmd($this->pythonPath),
            escapeshellcmd($this->scriptPath),
            escapeshellarg($videoId),
            escapeshellarg($languagesString)
        );
    }

    /**
     * Execute the Python script and return the output.
     *
     * @throws YouTubeTranscriptionException
     */
    private function executeScript(string $command): string
    {
        try {
            $process = Process::path(dirname($this->scriptPath))
                ->run($command);

            $exitCode = $process->exitCode();
            $output = $process->output();
            $errorOutput = $process->errorOutput();

            if ($exitCode !== 0) {
                $this->handleScriptError($command, $exitCode, $errorOutput ?: $output);
            }

            return trim($output);
        } catch (YouTubeTranscriptionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new YouTubeTranscriptionException(
                code: YouTubeTranscriptionException::ERROR_PYTHON_SCRIPT_FAILED,
                message: 'Failed to execute YouTube transcription script',
                command: $command,
                previous: $e,
            );
        }
    }

    /**
     * Handle script execution errors.
     *
     * @throws YouTubeTranscriptionException
     */
    private function handleScriptError(string $command, int $exitCode, string $errorOutput): void
    {
        // Parse error messages from the Python script output
        if (str_contains($errorOutput, 'Video unavailable')) {
            throw YouTubeTranscriptionException::videoNotFound(
                $this->extractVideoIdFromCommand($command)
            );
        }

        if (str_contains($errorOutput, 'No transcripts were found')) {
            throw YouTubeTranscriptionException::transcriptUnavailable(
                $this->extractVideoIdFromCommand($command)
            );
        }

        if (str_contains($errorOutput, 'Could not find') || str_contains($errorOutput, 'not found')) {
            throw YouTubeTranscriptionException::transcriptUnavailable(
                $this->extractVideoIdFromCommand($command)
            );
        }

        throw YouTubeTranscriptionException::scriptFailed($command, $exitCode, $errorOutput);
    }

    /**
     * Extract video ID from command string.
     */
    private function extractVideoIdFromCommand(string $command): string
    {
        // Find the first argument that looks like a video ID (11 chars, alphanumeric)
        preg_match('/[a-zA-Z0-9_-]{11}/', $command, $matches);

        return $matches[0] ?? 'unknown';
    }

    /**
     * Parse the JSON response from Python script.
     *
     * @throws YouTubeTranscriptionException
     */
    private function parseResponse(string $response, string $videoId): YouTubeTranscription
    {
        if (empty($response)) {
            throw YouTubeTranscriptionException::noTranscript($videoId);
        }

        // Clean up the response - remove Python-style string formatting if present
        $cleanedResponse = $this->cleanPythonOutput($response);

        try {
            $data = json_decode($cleanedResponse, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw YouTubeTranscriptionException::parseFailed($response);
            }

            // Handle the case where Python returns a dict with segments
            if (isset($data['transcript']) && is_array($data['transcript'])) {
                $data = $data['transcript'];
            }

            // Handle the case where segments are nested
            if (isset($data['segments']) && is_array($data['segments'])) {
                $data = $data['segments'];
            }

            // Ensure we have an array of segments
            if (!empty($data) && !is_array(reset($data))) {
                // Single segment object, wrap in array
                $data = [$data];
            }

            if (empty($data)) {
                throw YouTubeTranscriptionException::noTranscript($videoId);
            }

            return YouTubeTranscription::fromRawPythonOutput($data);
        } catch (YouTubeTranscriptionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw YouTubeTranscriptionException::parseFailed($response, $e);
        }
    }

    /**
     * Clean Python output to make it valid JSON.
     *
     * The Python youtube_transcript_api returns output like:
     * [[{'duration': 5.04, 'start': 1.92, 'text': '...'}]]
     *
     * This needs to be converted to valid JSON format.
     */
    private function cleanPythonOutput(string $output): string
    {
        // Remove the outer array brackets if present (Python wraps dicts in arrays)
        $cleaned = trim($output);

        // Handle single quotes used in Python dict representation
        // Convert Python single quotes to JSON double quotes patterns
        $cleaned = preg_replace("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/", '"$1"', $cleaned);

        // Handle True/False/None Python literals
        $cleaned = str_replace(': True', ': true', $cleaned);
        $cleaned = str_replace(': False', ': false', $cleaned);
        $cleaned = str_replace(': None', ': null', $cleaned);

        // Handle trailing commas (Python allows them, JSON doesn't)
        $cleaned = preg_replace('/,(\s*[}\]])/', '$1', $cleaned);

        return $cleaned;
    }
}
