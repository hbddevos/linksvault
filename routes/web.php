<?php

use App\Ai\Agents\YoutubeTranscriptSummary;
use App\Http\Controllers\GlmController;
use App\Http\Controllers\LinkShareController;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;

// Routes pour GLM API
Route::prefix('glm')->group(function () {
    Route::post('/chat', [GlmController::class, 'simpleChat'])->name('glm.chat');
    Route::post('/chat/history', [GlmController::class, 'chatWithHistory'])->name('glm.chat.history');
    Route::post('/chat/stream', [GlmController::class, 'chatStream'])->name('glm.chat.stream');
});

// Route de tracking pour les liens partagés
Route::get('/share/{token}', [LinkShareController::class, 'redirect'])
    ->name('links.share.redirect');


    Route::get('/', function () {

    // $subtitles = (new \App\Services\GoogleClient\YouTube\YouTubeTranscriptService())->getTranscript('jHRHV1MNR5s');

    // dd($subtitles);

    // dump(str($subtitles['full_text'])->limit(500)->value());

    // https://www.youtube.com/watch?v=WJL-WFsIpi4

// youtube_transcript_api <first_video_id> <second_video_id> ... --languages de en
    dd(Process::run("youtube_transcript_api WJL-WFsIpi4 --languages en fr es de")->output());


    // dump(Youtube::getVideoInfo(Youtube::parseVidFromURL('https://youtu.be/J0W_Ety8j6Q?si=dqblijCl0KsMtfUC'))->snippet->title);




    // $agent = (new YoutubeTranscriptSummary())->prompt(
    //     "Anslyse et génère le résumé {$datas['scripts']}",
    //     model: 'openai/gpt-oss-120b',
    //     provider: Lab::Ollama
    // );

    // dump($datas['scripts'], $datas['title']);
    // dd($agent->text);

    return view('welcome');
    // return $agent->then(function (StreamedAgentResponse $response) {
    //         // $response->text, $response->events, $response->usage...

    //         dd($response->text);
    //     });;
});