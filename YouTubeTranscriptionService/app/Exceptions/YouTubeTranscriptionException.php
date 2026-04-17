<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when YouTube transcription operations fail.
 */
class YouTubeTranscriptionException extends Exception
{
    public const int ERROR_VIDEO_NOT_FOUND = 1001;

    public const int ERROR_TRANSCRIPT_UNAVAILABLE = 1002;

    public const int ERROR_PYTHON_SCRIPT_FAILED = 1003;

    public const int ERROR_INVALID_RESPONSE = 1004;

    public const int ERROR_NETWORK_ERROR = 1005;

    public const int ERROR_NO_TRANSCRIPT = 1006;

    public const int ERROR_PARSE_FAILED = 1007;

    private const ERROR_MESSAGES = [
        self::ERROR_VIDEO_NOT_FOUND => 'Video not found or is unavailable',
        self::ERROR_TRANSCRIPT_UNAVAILABLE => 'Transcript is not available for this video',
        self::ERROR_PYTHON_SCRIPT_FAILED => 'Python script execution failed',
        self::ERROR_INVALID_RESPONSE => 'Invalid response from YouTube API',
        self::ERROR_NETWORK_ERROR => 'Network error occurred while fetching transcript',
        self::ERROR_NO_TRANSCRIPT => 'No transcript segments were returned',
        self::ERROR_PARSE_FAILED => 'Failed to parse transcript data',
    ];

    private const HTTP_STATUS_CODES = [
        self::ERROR_VIDEO_NOT_FOUND => 404,
        self::ERROR_TRANSCRIPT_UNAVAILABLE => 404,
        self::ERROR_PYTHON_SCRIPT_FAILED => 500,
        self::ERROR_INVALID_RESPONSE => 502,
        self::ERROR_NETWORK_ERROR => 503,
        self::ERROR_NO_TRANSCRIPT => 404,
        self::ERROR_PARSE_FAILED => 500,
    ];

    private ?string $rawOutput;

    private ?string $command;

    public function __construct(
        int $code,
        ?string $message = null,
        ?string $rawOutput = null,
        ?string $command = null,
        ?Throwable $previous = null,
    ) {
        $finalMessage = $message ?? (self::ERROR_MESSAGES[$code] ?? 'Unknown YouTube transcription error');

        parent::__construct($finalMessage, $code, $previous);

        $this->rawOutput = $rawOutput;
        $this->command = $command;
    }

    /**
     * Get the HTTP status code that should be returned for this error.
     */
    public function getHttpStatusCode(): int
    {
        return self::HTTP_STATUS_CODES[$this->getCode()] ?? 500;
    }

    /**
     * Get the raw output from the Python script (if available).
     */
    public function getRawOutput(): ?string
    {
        return $this->rawOutput;
    }

    /**
     * Get the command that was executed (if available).
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * Check if this exception has raw output data.
     */
    public function hasRawOutput(): bool
    {
        return $this->rawOutput !== null;
    }

    /**
     * Check if this exception has command data.
     */
    public function hasCommand(): bool
    {
        return $this->command !== null;
    }

    /**
     * Create an exception for video not found.
     */
    public static function videoNotFound(string $videoId): self
    {
        return new self(
            code: self::ERROR_VIDEO_NOT_FOUND,
            message: "Video '{$videoId}' not found or is unavailable",
        );
    }

    /**
     * Create an exception for transcript unavailable.
     */
    public static function transcriptUnavailable(string $videoId): self
    {
        return new self(
            code: self::ERROR_TRANSCRIPT_UNAVAILABLE,
            message: "Transcript is not available for video '{$videoId}'",
        );
    }

    /**
     * Create an exception for Python script failure.
     */
    public static function scriptFailed(
        string $command,
        int $exitCode,
        string $errorOutput,
        ?Throwable $previous = null
    ): self {
        return new self(
            code: self::ERROR_PYTHON_SCRIPT_FAILED,
            message: "Python script failed with exit code {$exitCode}",
            rawOutput: $errorOutput,
            command: $command,
            previous: $previous,
        );
    }

    /**
     * Create an exception for parse failure.
     */
    public static function parseFailed(string $rawOutput, ?Throwable $previous = null): self
    {
        return new self(
            code: self::ERROR_PARSE_FAILED,
            message: 'Failed to parse transcript JSON data',
            rawOutput: $rawOutput,
            previous: $previous,
        );
    }

    /**
     * Create an exception for no transcript returned.
     */
    public static function noTranscript(string $videoId): self
    {
        return new self(
            code: self::ERROR_NO_TRANSCRIPT,
            message: "No transcript segments returned for video '{$videoId}'",
        );
    }

    /**
     * Convert the exception to an array for API responses.
     *
     * @return array{error: string, code: int, message: string, details?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $response = [
            'error' => 'youtube_transcription_error',
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];

        if (app()->has('debug') && app('debug')) {
            $response['details'] = [
                'http_status' => $this->getHttpStatusCode(),
            ];

            if ($this->hasCommand()) {
                $response['details']['command'] = $this->getCommand();
            }

            if ($this->hasRawOutput()) {
                $response['details']['raw_output'] = $this->getRawOutput();
            }
        }

        return $response;
    }
}
