<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\YouTube\YouTubeTranscription;
use App\DTOs\YouTube\YouTubeTranscriptSegment;
use PHPUnit\Framework\TestCase;

class YouTubeTranscriptionTest extends TestCase
{
    public function testFromJsonParsesValidJson(): void
    {
        $json = '[
            {"duration": 5.04, "start": 1.92, "text": "Hello World"},
            {"duration": 4.48, "start": 6.96, "text": "This is a test"}
        ]';

        $transcription = YouTubeTranscription::fromJson($json);

        $this->assertCount(2, $transcription);
        $this->assertFalse($transcription->isEmpty());
    }

    public function testFromRawPythonOutputHandlesCorrectData(): void
    {
        $rawData = [
            ['duration' => 5.04, 'start' => 1.92, 'text' => 'Hello'],
            ['duration' => 4.48, 'start' => 6.96, 'text' => 'World'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);

        $this->assertCount(2, $transcription);
        $this->assertEquals('Hello', $transcription->getSegment(0)->text);
        $this->assertEquals('World', $transcription->getSegment(1)->text);
    }

    public function testGetPlainTextConcatenatesAllText(): void
    {
        $rawData = [
            ['duration' => 1.0, 'start' => 0.0, 'text' => 'First'],
            ['duration' => 2.0, 'start' => 1.0, 'text' => 'Second'],
            ['duration' => 3.0, 'start' => 3.0, 'text' => 'Third'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);

        $this->assertEquals('First Second Third', $transcription->getPlainText());
    }

    public function testGetTextWithTimestampsIncludesAllSegments(): void
    {
        $rawData = [
            ['duration' => 5.04, 'start' => 1.92, 'text' => 'Hello'],
            ['duration' => 4.48, 'start' => 6.96, 'text' => 'World'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);
        $textWithTimestamps = $transcription->getTextWithTimestamps();

        $this->assertStringContainsString('[00:00:01.920] Hello', $textWithTimestamps);
        $this->assertStringContainsString('[00:00:06.960] World', $textWithTimestamps);
    }

    public function testGetTotalDurationReturnsLastSegmentEnd(): void
    {
        $rawData = [
            ['duration' => 5.04, 'start' => 1.92, 'text' => 'First'],
            ['duration' => 4.48, 'start' => 6.96, 'text' => 'Second'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);

        // Last segment: start=6.96, duration=4.48, so end=11.44
        $this->assertEqualsWithDelta(11.44, $transcription->getTotalDuration(), 0.01);
    }

    public function testGetTotalDurationReturnsZeroForEmpty(): void
    {
        $transcription = YouTubeTranscription::fromRawPythonOutput([]);

        $this->assertEquals(0.0, $transcription->getTotalDuration());
    }

    public function testGetSegmentsInRangeFiltersCorrectly(): void
    {
        $rawData = [
            ['duration' => 5.0, 'start' => 0.0, 'text' => 'Zero'],
            ['duration' => 5.0, 'start' => 5.0, 'text' => 'Five'],
            ['duration' => 5.0, 'start' => 10.0, 'text' => 'Ten'],
            ['duration' => 5.0, 'start' => 15.0, 'text' => 'Fifteen'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);

        $filtered = $transcription->getSegmentsInRange(5.0, 12.0);

        $this->assertCount(2, $filtered);
        $this->assertEquals('Five', $filtered[0]->text);
        $this->assertEquals('Ten', $filtered[1]->text);
    }

    public function testIteratorCanBeUsedInForeach(): void
    {
        $rawData = [
            ['duration' => 1.0, 'start' => 0.0, 'text' => 'A'],
            ['duration' => 2.0, 'start' => 1.0, 'text' => 'B'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);
        $texts = [];

        foreach ($transcription as $segment) {
            $texts[] = $segment->text;
        }

        $this->assertEquals(['A', 'B'], $texts);
    }

    public function testToArrayReturnsAllSegments(): void
    {
        $rawData = [
            ['duration' => 1.0, 'start' => 0.0, 'text' => 'Test'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);
        $array = $transcription->toArray();

        $this->assertCount(1, $array);
        $this->assertEquals(1.0, $array[0]['duration']);
        $this->assertEquals(0.0, $array[0]['start']);
        $this->assertEquals('Test', $array[0]['text']);
    }

    public function testCountableInterface(): void
    {
        $rawData = [
            ['duration' => 1.0, 'start' => 0.0, 'text' => 'One'],
            ['duration' => 2.0, 'start' => 1.0, 'text' => 'Two'],
            ['duration' => 3.0, 'start' => 3.0, 'text' => 'Three'],
        ];

        $transcription = YouTubeTranscription::fromRawPythonOutput($rawData);

        $this->assertCount(3, $transcription);
    }

    public function testFromJsonThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        YouTubeTranscription::fromJson('invalid json');
    }

    public function testFromJsonThrowsOnNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        YouTubeTranscription::fromJson('"just a string"');
    }
}
