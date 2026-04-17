# YouTube Transcription Service for Laravel

A Laravel service for fetching and parsing YouTube video transcriptions using the `youtube_transcript_api` Python library.

## Installation

### 1. Ensure Python Dependencies

On your server, install the `youtube_transcript_api` package:

```bash
# Using the same Python that Laravel will use
pip install youtube_transcript_api

# Or using conda (if your server uses conda)
conda install -c conda-forge youtube-transcript-api
```

### 2. Create the Python Script

Create the script at `storage/app/scripts/get_transcript.py`:

```python
#!/usr/bin/env python3
import json
import sys
from youtube_transcript_api import YouTubeTranscriptApi

def get_transcript(video_id, languages):
    try:
        transcript = YouTubeTranscriptApi.get_transcript(video_id, languages=languages)
        print(json.dumps(transcript))
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    video_id = sys.argv[1]
    languages = sys.argv[3].split() if len(sys.argv) > 3 else ['en']
    get_transcript(video_id, languages)
```

Make it executable:
```bash
chmod +x storage/app/scripts/get_transcript.py
```

### 3. Configure Environment Variables

Add to your `.env` file:

```env
# YouTube Transcription Settings
YOUTUBE_TRANSCRIPT_PYTHON_PATH=/path/to/python
YOUTUBE_TRANSCRIPT_SCRIPT_PATH=/path/to/your/project/storage/app/scripts/get_transcript.py
YOUTUBE_TRANSCRIPT_LANGUAGES=en,fr,es,de
YOUTUBE_TRANSCRIPT_TIMEOUT=30
YOUTUBE_TRANSCRIPT_ENABLED=true
```

## Usage

### Basic Usage

```php
use App\Services\YouTube\YouTubeTranscriptionService;

// Get the service from the container
$service = app(YouTubeTranscriptionService::class);

// Fetch transcription
$transcription = $service->getTranscription('ulJTCVm3wXo');

// Get plain text
$text = $transcription->getPlainText();
// "A single note might seem like a simple sound, but when it finds others, a melody is born..."

// Get text with timestamps
$textWithTimestamps = $transcription->getTextWithTimestamps();
// "[00:00:01.920] A single note might seem like..."
// "[00:00:06.960] a melody is born that can give meaning..."

// Get total duration
$duration = $transcription->getTotalDuration(); // in seconds
```

### Specify Languages

```php
// Try specific languages (ordered by priority)
$transcription = $service->getTranscription('ulJTCVm3wXo', ['fr', 'en']);
```

### Check Availability

```php
// Check if transcript is available
if ($service->isAvailable('ulJTCVm3wXo')) {
    $transcription = $service->getTranscription('ulJTCVm3wXo');
}
```

### Multi-language Fallback

```php
// Automatically tries languages in order until one works
$transcription = $service->getTranscriptionMultiLanguage('ulJTCVm3wXo');
```

### Work with Segments

```php
foreach ($transcription as $segment) {
    echo sprintf(
        "[%s - %s] %s\n",
        $segment->getFormattedStartTime(),
        $segment->getFormattedEndTime(),
        $segment->text
    );
}
```

### Filter by Time Range

```php
// Get segments between 10 and 30 seconds
$segments = $transcription->getSegmentsInRange(10.0, 30.0);
```

## DTO Structure

### YouTubeTranscription

| Method | Description |
|--------|-------------|
| `getPlainText()` | Concatenated text without timestamps |
| `getTextWithTimestamps()` | Text with `[HH:MM:SS.mmm]` timestamps |
| `getTotalDuration()` | Total video duration in seconds |
| `getSegmentsInRange(start, end)` | Filter segments by time range |
| `getSegmentsByInterval(interval)` | Group segments by time intervals |
| `isEmpty()` | Check if transcription has segments |
| `count()` | Number of segments |

### YouTubeTranscriptSegment

| Property | Description |
|----------|-------------|
| `duration` | Segment duration in seconds |
| `start` | Start time in seconds |
| `text` | The transcript text |

| Method | Description |
|--------|-------------|
| `getEndTime()` | End time (start + duration) |
| `getFormattedStartTime()` | Formatted start time |
| `getFormattedEndTime()` | Formatted end time |

## Error Handling

```php
use App\Exceptions\YouTubeTranscriptionException;

try {
    $transcription = $service->getTranscription('invalid_id');
} catch (YouTubeTranscriptionException $e) {
    switch ($e->getCode()) {
        case YouTubeTranscriptionException::ERROR_VIDEO_NOT_FOUND:
            // Handle video not found
            break;
        case YouTubeTranscriptionException::ERROR_TRANSCRIPT_UNAVAILABLE:
            // Handle transcript not available
            break;
        case YouTubeTranscriptionException::ERROR_PYTHON_SCRIPT_FAILED:
            // Handle script failure
            Log::error('Script failed', [
                'command' => $e->getCommand(),
                'output' => $e->getRawOutput(),
            ]);
            break;
    }
}
```

## Testing

```bash
./vendor/bin/phpunit tests/Unit/Services/YouTubeTranscriptionServiceTest.php
```

## File Structure

```
app/
├── DTOs/
│   └── YouTube/
│       ├── YouTubeTranscriptSegment.php
│       └── YouTubeTranscription.php
├── Exceptions/
│   └── YouTubeTranscriptionException.php
└── Services/
    └── YouTube/
        └── YouTubeTranscriptionService.php
config/
└── youtube_transcription.php
tests/
└── Unit/
    ├── DTOs/
    │   └── YouTubeTranscriptionTest.php
    └── Services/
        └── YouTubeTranscriptionServiceTest.php
```

## License

MIT
