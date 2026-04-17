<?php

declare(strict_types=1);

namespace App\DTOs\YouTube;

/**
 * Data Transfer Object representing a single caption segment from YouTube.
 */
final readonly class YouTubeTranscriptSegment
{
    public function __construct(
        public float $duration,
        public float $start,
        public string $text,
    ) {}

    /**
     * Create an instance from an array (typically parsed from JSON).
     *
     * @param  array{duration?: float|int, start?: float|int, text?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            duration: (float) ($data['duration'] ?? 0),
            start: (float) ($data['start'] ?? 0),
            text: (string) ($data['text'] ?? ''),
        );
    }

    /**
     * Convert the segment to an array.
     *
     * @return array{duration: float, start: float, text: string}
     */
    public function toArray(): array
    {
        return [
            'duration' => $this->duration,
            'start' => $this->start,
            'text' => $this->text,
        ];
    }

    /**
     * Get the end timestamp of this segment.
     */
    public function getEndTime(): float
    {
        return $this->start + $this->duration;
    }

    /**
     * Format the start time as HH:MM:SS.mmm
     */
    public function getFormattedStartTime(): string
    {
        return $this->formatTimestamp($this->start);
    }

    /**
     * Format the end time as HH:MM:SS.mmm
     */
    public function getFormattedEndTime(): string
    {
        return $this->formatTimestamp($this->getEndTime());
    }

    /**
     * Format a timestamp (in seconds) to HH:MM:SS.mmm format.
     */
    private function formatTimestamp(float $seconds): string
    {
        $hours = (int) ($seconds / 3600);
        $minutes = (int) (($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $secs);
    }
}
