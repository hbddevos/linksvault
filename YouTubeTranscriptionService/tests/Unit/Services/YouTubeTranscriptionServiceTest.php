<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\YouTube\YouTubeTranscription;
use App\Exceptions\YouTubeTranscriptionException;
use App\Services\YouTube\YouTubeTranscriptionService;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\TestCase;

class YouTubeTranscriptionServiceTest extends TestCase
{
    private YouTubeTranscriptionService $service;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new YouTubeTranscriptionService(
            pythonPath: 'python3',
            scriptPath: '/path/to/get_transcript.py',
            defaultLanguages: ['en', 'fr', 'es', 'de']
        );

        $this->fixturePath = __DIR__.'/../../fixtures';
    }

    public function test_get_transcription_parses_valid_response(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'A single note might seem like a simple sound, but when it finds others,'}, {'duration': 4.48, 'start': 6.96, 'text': 'a melody is born that can give meaning to everything.'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('ulJTCVm3wXo');

        $this->assertInstanceOf(YouTubeTranscription::class, $transcription);
        $this->assertCount(2, $transcription);
        $this->assertFalse($transcription->isEmpty());
    }

    public function test_get_plain_text_returns_concatenated_text(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'Hello world'}, {'duration': 4.48, 'start': 6.96, 'text': 'This is a test'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('test12345678');

        $this->assertEquals('Hello world This is a test', $transcription->getPlainText());
    }

    public function test_get_text_with_timestamps_returns_formatted_text(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'Hello'}, {'duration': 4.48, 'start': 6.96, 'text': 'World'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('test12345678');

        $textWithTimestamps = $transcription->getTextWithTimestamps();

        $this->assertStringContainsString('[00:00:01.920] Hello', $textWithTimestamps);
        $this->assertStringContainsString('[00:00:06.960] World', $textWithTimestamps);
    }

    public function test_get_total_duration_calculates_correctly(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'First'}, {'duration': 4.48, 'start': 6.96, 'text': 'Second'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('test12345678');

        // Last segment starts at 6.96 and has duration 4.48, so total should be 6.96 + 4.48 = 11.44
        $this->assertEqualsWithDelta(11.44, $transcription->getTotalDuration(), 0.01);
    }

    public function test_video_not_found_throws_exception(): void
    {
        $errorOutput = 'Video unavailable';

        Process::fake([
            '*' => Process::result($errorOutput, 1),
        ]);

        $this->expectException(YouTubeTranscriptionException::class);
        $this->expectExceptionCode(YouTubeTranscriptionException::ERROR_VIDEO_NOT_FOUND);

        try {
            $this->service->getTranscription('invalid123456');
        } catch (YouTubeTranscriptionException $e) {
            $this->assertEquals(404, $e->getHttpStatusCode());
            throw $e;
        }
    }

    public function test_transcript_unavailable_throws_exception(): void
    {
        $errorOutput = 'No transcripts were found for this video';

        Process::fake([
            '*' => Process::result($errorOutput, 1),
        ]);

        $this->expectException(YouTubeTranscriptionException::class);
        $this->expectExceptionCode(YouTubeTranscriptionException::ERROR_TRANSCRIPT_UNAVAILABLE);

        try {
            $this->service->getTranscription('notext1234567');
        } catch (YouTubeTranscriptionException $e) {
            $this->assertEquals(404, $e->getHttpStatusCode());
            throw $e;
        }
    }

    public function test_invalid_video_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid YouTube video ID format');

        $this->service->getTranscription('invalid-id');
    }

    public function test_empty_video_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->getTranscription('');
    }

    public function test_invalid_language_code_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language code format');

        $this->service->getTranscription('ulJTCVm3wXo', ['INVALID']);
    }

    public function test_empty_languages_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one language code must be provided');

        $this->service->getTranscription('ulJTCVm3wXo', []);
    }

    public function test_is_available_returns_true_when_transcript_exists(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'Test'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $this->assertTrue($this->service->isAvailable('ulJTCVm3wXo'));
    }

    public function test_is_available_returns_false_when_transcript_unavailable(): void
    {
        $errorOutput = 'No transcripts were found';

        Process::fake([
            '*' => Process::result($errorOutput, 1),
        ]);

        $this->assertFalse($this->service->isAvailable('notext1234567'));
    }

    public function test_get_segments_in_range_filters_correctly(): void
    {
        $rawOutput = "[[
            {'duration': 5.0, 'start': 0.0, 'text': 'Segment 1'},
            {'duration': 5.0, 'start': 5.0, 'text': 'Segment 2'},
            {'duration': 5.0, 'start': 10.0, 'text': 'Segment 3'},
            {'duration': 5.0, 'start': 15.0, 'text': 'Segment 4'}
        ]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('test12345678');

        $filtered = $transcription->getSegmentsInRange(5.0, 12.0);

        $this->assertCount(2, $filtered);
        $this->assertEquals('Segment 2', $filtered[0]->text);
        $this->assertEquals('Segment 3', $filtered[1]->text);
    }

    public function test_get_segments_by_interval_groups_correctly(): void
    {
        $rawOutput = "[[
            {'duration': 5.0, 'start': 0.0, 'text': 'A'},
            {'duration': 5.0, 'start': 25.0, 'text': 'B'},
            {'duration': 5.0, 'start': 35.0, 'text': 'C'}
        ]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $transcription = $this->service->getTranscription('test12345678');

        $grouped = $transcription->getSegmentsByInterval(30.0);

        $this->assertCount(2, $grouped);
        $this->assertEquals(0.0, $grouped[0]['start']);
        $this->assertEquals('A', $grouped[0]['text']);
        $this->assertEquals(30.0, $grouped[1]['start']);
        $this->assertEquals('B C', $grouped[1]['text']);
    }

    public function test_get_transcription_multi_language_tries_languages(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'French text'}]]";

        // First call fails (English unavailable), second succeeds (French)
        Process::fakeSequence()
            ->push(Process::result('', 1))  // English fails
            ->push(Process::result($rawOutput, 0));  // French succeeds

        $transcription = $this->service->getTranscriptionMultiLanguage('test12345678', ['en', 'fr']);

        $this->assertEquals('French text', $transcription->getPlainText());
    }

    public function test_service_uses_configured_languages(): void
    {
        $rawOutput = "[[{'duration': 5.04, 'start': 1.92, 'text': 'Test'}]]";

        Process::fake([
            '*' => Process::result($rawOutput, 0),
        ]);

        $service = new YouTubeTranscriptionService(
            pythonPath: 'python3',
            scriptPath: '/path/to/script.py',
            defaultLanguages: ['de']
        );

        $service->getTranscription('test12345678');

        // The process should have been called with German as the language
        Process::assertRan(function ($command) {
            return str_contains($command, '--languages de');
        });
    }
}
