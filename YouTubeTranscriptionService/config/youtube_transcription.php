<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | YouTube Transcript API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the YouTube transcription service.
    |
    */

    // Path to the Python interpreter
    'python_path' => env('YOUTUBE_TRANSCRIPT_PYTHON_PATH', 'python3'),

    // Path to the get_transcript.py script
    'script_path' => env('YOUTUBE_TRANSCRIPT_SCRIPT_PATH', base_path('storage/app/scripts/get_transcript.py')),

    // Default languages to try (ordered by priority)
    'languages' => env('YOUTUBE_TRANSCRIPT_LANGUAGES', 'en,fr,es,de'),

    // Timeout for the script execution in seconds
    'timeout' => env('YOUTUBE_TRANSCRIPT_TIMEOUT', 30),

    // Enable/disable transcript fetching
    'enabled' => env('YOUTUBE_TRANSCRIPT_ENABLED', true),

];
