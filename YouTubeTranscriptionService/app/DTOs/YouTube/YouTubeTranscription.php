<?php

declare(strict_types=1);

namespace App\DTOs\YouTube;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Data Transfer Object representing a complete YouTube video transcription.
 *
 * @implements IteratorAggregate<int, YouTubeTranscriptSegment>
 */
final readonly class YouTubeTranscription implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, YouTubeTranscriptSegment>  $segments
     */
    public function __construct(
        public array $segments,
    ) {}

    /**
     * Create an instance from raw Python output (JSON-like array of dictionaries).
     *
     * @param  array<int, array{duration?: float|int, start?: float|int, text?: string}>  $data
     */
    public static function fromRawPythonOutput(array $data): self
    {
        $segments = array_map(
            fn (array $segment) => YouTubeTranscriptSegment::fromArray($segment),
            $data
        );

        return new self($segments);
    }

    /**
     * Create an instance from a JSON string (Python output).
     */
    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('JSON must decode to an array.');
        }

        return self::fromRawPythonOutput($decoded);
    }

    /**
     * Get the complete transcription text with timestamps.
     *
     * Format: [HH:MM:SS] Text
     */
    public function getTextWithTimestamps(): string
    {
        $lines = array_map(
            fn (YouTubeTranscriptSegment $segment) => sprintf(
                '[%s] %s',
                $segment->getFormattedStartTime(),
                trim($segment->text)
            ),
            $this->segments
        );

        return implode("\n", $lines);
    }

    /**
     * Get the plain text transcription (without timestamps).
     */
    public function getPlainText(): string
    {
        return implode(' ', array_map(
            fn (YouTubeTranscriptSegment $segment) => trim($segment->text),
            $this->segments
        ));
    }

    /**
     * Get segments grouped by time ranges.
     *
     * @param  float  $intervalSeconds  Group segments within this time interval (default: 30 seconds)
     * @return array<int, array{start: float, end: float, text: string}>
     */
    public function getSegmentsByInterval(float $intervalSeconds = 30.0): array
    {
        $groups = [];
        $currentGroup = null;

        foreach ($this->segments as $segment) {
            $groupKey = (int) ($segment->start / $intervalSeconds);
            $groupStart = $groupKey * $intervalSeconds;
            $groupEnd = $groupStart + $intervalSeconds;

            if ($currentGroup === null || $currentGroup['key'] !== $groupKey) {
                if ($currentGroup !== null) {
                    $groups[] = [
                        'start' => $currentGroup['start'],
                        'end' => $currentGroup['end'],
                        'text' => trim(implode(' ', $currentGroup['texts'])),
                    ];
                }

                $currentGroup = [
                    'key' => $groupKey,
                    'start' => $groupStart,
                    'end' => $groupEnd,
                    'texts' => [],
                ];
            }

            $currentGroup['texts'][] = $segment->text;
        }

        // Add the last group
        if ($currentGroup !== null) {
            $groups[] = [
                'start' => $currentGroup['start'],
                'end' => $currentGroup['end'],
                'text' => trim(implode(' ', $currentGroup['texts'])),
            ];
        }

        return $groups;
    }

    /**
     * Get the total duration of the transcription in seconds.
     */
    public function getTotalDuration(): float
    {
        if (empty($this->segments)) {
            return 0.0;
        }

        $lastSegment = end($this->segments);

        return $lastSegment->getEndTime();
    }

    /**
     * Convert to array format.
     *
     * @return array<int, array{duration: float, start: float, text: string}>
     */
    public function toArray(): array
    {
        return array_map(
            fn (YouTubeTranscriptSegment $segment) => $segment->toArray(),
            $this->segments
        );
    }

    /**
     * Get the number of segments.
     */
    public function count(): int
    {
        return count($this->segments);
    }

    /**
     * Get an iterator for the segments.
     *
     * @return ArrayIterator<int, YouTubeTranscriptSegment>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->segments);
    }

    /**
     * Check if the transcription is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->segments);
    }

    /**
     * Get a specific segment by index.
     */
    public function getSegment(int $index): ?YouTubeTranscriptSegment
    {
        return $this->segments[$index] ?? null;
    }

    /**
     * Get segments within a time range.
     *
     * @return array<int, YouTubeTranscriptSegment>
     */
    public function getSegmentsInRange(float $startTime, float $endTime): array
    {
        return array_values(array_filter(
            $this->segments,
            fn (YouTubeTranscriptSegment $segment) => $segment->start >= $startTime && $segment->start <= $endTime
        ));
    }
}
