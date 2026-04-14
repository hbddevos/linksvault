<?php

use Alaouy\Youtube\Facades\Youtube;
use App\Ai\Agents\YoutubeTranscriptSummary;
use App\Services\ContentDetectionService;
use App\Services\YouTubeTranscriptService;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;

Route::get('/', function () {

    $subtitles = (new \App\Services\GoogleClient\YouTube\YouTubeTranscriptService())->getTranscript('jHRHV1MNR5s');

    // dd($subtitles);

    dump(str($subtitles['full_text'])->limit(500)->value());

// youtube_transcript_api <first_video_id> <second_video_id> ... --languages de en
    dd(Process::run("youtube_transcript_api ReqHcXhYzWA --languages en fr")->output());


    dump(Youtube::getVideoInfo(Youtube::parseVidFromURL('https://youtu.be/J0W_Ety8j6Q?si=dqblijCl0KsMtfUC'))->snippet->title);




    // $agent = (new YoutubeTranscriptSummary)->prompt(
    //     "Anslyse et génère le résumé {$datas['scripts']}",
    //     model: 'openai/gpt-oss-120b',
    //     provider: Lab::Groq
    // );

    // dump($datas['scripts'], $datas['title']);
    // dd($agent->text);

    // return view('welcome');
    // return $agent->then(function (StreamedAgentResponse $response) {
    //         // $response->text, $response->events, $response->usage...

    //         dd($response->text);
    //     });;
});
