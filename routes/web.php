<?php

use App\Ai\Agents\YoutubeTranscriptSummary;
use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\GlmController;
use App\Http\Controllers\LinkShareController;
use App\Services\WebPageMetadataService;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;
use App\Services\GlmService;

// Routes pour GLM API
Route::prefix('glm')->group(function () {
    Route::post('/chat', [GlmController::class, 'simpleChat'])->name('glm.chat');
    Route::post('/chat/history', [GlmController::class, 'chatWithHistory'])->name('glm.chat.history');
    Route::post('/chat/stream', [GlmController::class, 'chatStream'])->name('glm.chat.stream');
});

// Route de tracking pour les liens partagés
Route::get('/share/{token}', [LinkShareController::class, 'redirect'])
    ->name('links.share.redirect');

Route::get('/team-invitations/{code}/accept', AcceptInvitationController::class)
    ->middleware(['web', 'signed'])
    ->name('filateams.invitations.accept');


    
Route::get('/', function () {


    // $response = app(GlmService::class)->chatSimple('Hello');
    // $answer = app(GlmService::class)->extractResponse($response);

    // dump($answer, $response);

    $process = Process::run("youtube_transcript_api ulJTCVm3wXo");

    $output = $process->output();

    // supprimer b""" au début et """ à la fin
    $output = preg_replace('/^b"""|"""$/', '', trim($output));

    $json = str_replace("'", '"', $output);
    $data = json_decode($json, true);
    $fullText = '';


    $webpageData = (new WebPageMetadataService())->fetchMetadata('https://docs.google.com/document/d/11uc-no4tXCTS9QA7USLGFiyIiOAjx6BvEyR7g1EpL0Q/edit?usp=sharing');

    dd($webpageData);
    // foreach ($data[0] as $segment) {
    //     $fullText .= $segment['text'] . ' ';
    // }

    // $fullText = trim($fullText);

    // echo $fullText;

    dd($output, $data);

    return view('welcome');
    // return $agent->then(function (StreamedAgentResponse $response) {
    //         // $response->text, $response->events, $response->usage...

    //         dd($response->text);
    //     });;
});