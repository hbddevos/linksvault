<?php

namespace App\Filament\Resources\Links\Schemas;

use Alaouy\Youtube\Youtube;
use App\Ai\Agents\YoutubeTranscriptSummary;
use App\Enums\ContentType;
use App\Models\Category;
use App\Services\ContentDetectionService;
use App\Services\YouTubeTranscriptService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class LinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->components(self::getComponents()),
            ]);
    }

    public static function getComponents(): array
    {
        return [
            Grid::make(2)
                ->components([
                    TextInput::make('url')
                        ->label(__('URL'))
                        ->required()
                        ->maxLength(2048)
                        ->autofocus()
                        ->url()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if (empty($state)) {
                                return;
                            }

                            $contentDetection = app(ContentDetectionService::class);
                            $analysis = $contentDetection->analyze($state);
                            $type = $analysis['type'];

                            // Auto-detect content type
                            $set('content_type', $type);

                            // Auto-generate title if empty
                            $title = $get('title');
                            if (empty($title)) {
                                $set('title', $contentDetection->generateTitleFromUrl($state, $analysis['type']));
                            }

                            if($type === 'youtube') {
                                $set('description', $contentDetection->getYoutubeVideoDescription($state));
                            }

                            // Auto-fill metadata
                            if (!empty($analysis['metadata'])) {
                                $set('metadata', json_encode($analysis['metadata']));
                            }
                        }),
                    TextInput::make('title')
                        ->label(__('Title'))
                        ->required()
                        ->maxLength(500),
                ]),
            MarkdownEditor::make('description')
                ->label(__('Description')),
            Hidden::make('metadata')
                ->default('{}'),
            Grid::make(3)
                ->components([
                    Select::make('content_type')
                        ->label(__('Content Type'))
                        ->options(collect(ContentType::cases())
                            ->mapWithKeys(fn(ContentType $case) => [$case->value => $case->label()])
                            ->toArray())
                        ->default(ContentType::Other->value),
                    Select::make('category_id')
                        ->label(__('Category'))
                        ->options(fn() => Category::pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    TextInput::make('objective')
                        ->label(__('Objective'))
                        ->maxLength(255),
                ]),
            Grid::make(2)
                ->components([
                    Toggle::make('is_favorite')
                        ->label(__('Favorite'))
                        ->default(false),
                    Toggle::make('is_archived')
                        ->label(__('Archived'))
                        ->default(false),
                ]),
            MarkdownEditor::make('ai_summary')
                ->afterLabel(
                    Action::make('generate_ai_summary')
                        ->label(__('Generate AI Summary'))
                        ->icon('heroicon-o-sparkles')
                        ->button()
                        ->color('primary')
                        ->action(function ($state, Set $set, Get $get) {

                            $contentDetection = app(ContentDetectionService::class);
                            $analysis = $contentDetection->analyze($get('url'));
                            $type = $analysis['type'];
                            $subtitle = "";
                            $lang = "fr"; // Langue par défaut : français


                            if ($type === 'youtube') {
                                try {
                                    $video_id = Youtube::parseVidFromURL($get('url'));
                                    $video_infos = \Alaouy\Youtube\Facades\Youtube::getVideoInfo($video_id);
                                    
                                    // Récupérer la langue de la vidéo et la normaliser
                                    $detectedLang = $video_infos->snippet->defaultLanguage ?? 'fr';
                                    
                                    // Normaliser le code de langue (extraire les 2 premières lettres)
                                    $lang = str_contains($detectedLang, '-') 
                                        ? explode('-', $detectedLang)[0] 
                                        : $detectedLang;
                                    
                                    $lang = strtolower($lang);

                                    // Récupérer la transcription avec gestion d'erreur
                                    $subtitles = (new \App\Services\GoogleClient\YouTube\YouTubeTranscriptService())
                                        ->getTranscript($video_id, language: $lang);

                                    if (!$subtitles || !isset($subtitles['full_text'])) {
                                        // Vérifier si on a des infos sur les langues disponibles
                                        $availableLangs = (new \App\Services\GoogleClient\YouTube\YouTubeTranscriptService())
                                            ->getAvailableLanguages($video_id);
                                        
                                        $langList = !empty($availableLangs) 
                                            ? implode(', ', array_column($availableLangs, 'language'))
                                            : 'aucune';
                                        
                                        $set('ai_summary', "⚠️ Impossible de récupérer la transcription.\n\n**Langues disponibles :** {$langList}\n\nVérifiez que la vidéo possède des sous-titres activés.");
                                        return;
                                    }

                                    // Afficher la stratégie utilisée (pour info)
                                    $strategyInfo = isset($subtitles['strategy_used']) 
                                        ? "\n\n*Stratégie : {$subtitles['strategy_used']}*" 
                                        : '';
                                    
                                    // Limiter le texte si trop long
                                    if (str_word_count($subtitles['full_text']) > 1000) {
                                        $subtitle = str($subtitles['full_text'])->limit(1000)->value();
                                    } else {
                                        $subtitle = $subtitles['full_text'];
                                    }
                                    
                                    // Générer le résumé avec l'agent AI
                                    $agent = (new YoutubeTranscriptSummary)->prompt(
                                        "Analyse et génère le résumé en français de cette transcription YouTube (langue originale : {$subtitles['language_code']}) : {$subtitle}",
                                        model: 'openai/gpt-oss-120b',
                                        provider: Lab::Groq
                                    );

                                    // Ajouter les métadonnées au résumé
                                    $metadata = "**Vidéo :** {$video_infos->snippet->title}\n";
                                    $metadata .= "**Langue :** {$subtitles['language']}";
                                    $metadata .= $strategyInfo;
                                    $metadata .= "\n\n---\n\n";
                                    
                                    $set('ai_summary', $metadata . $agent->text);
                                    
                                } catch (\Exception $e) {
                                    Log::error("Erreur génération résumé AI", [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                        'url' => $get('url'),
                                        'language' => $lang
                                    ]);
                                    
                                    $set('ai_summary', "❌ **Erreur lors de la génération du résumé**\n\n" . $e->getMessage());
                                }
                            } else {
                                $set('ai_summary', "ℹ️ Pas de résumé disponible pour ce type de lien");
                            }

                        })

                )
                ->label(__('AI Summary'))
                ->columnSpanFull()
                ->helperText(__('AI summary will be generated automatically')),
        ];
    }
}
