<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2083)->comment('Max URL length');
            $table->char('url_hash', 64)->nullable()->comment('SHA256 of URL for duplicate detection');
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->enum('content_type', ['youtube', 'drive', 'article', 'pdf', 'image', 'other'])->default('other');
            $table->json('metadata')->nullable()->comment('Content-specific metadata');
            $table->text('ai_summary')->nullable();
            $table->enum('ai_summary_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('objective')->nullable()->comment('User personal objective');
            $table->string('favicon_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamp('last_visited_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'url_hash']);
            $table->index(['user_id', 'content_type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'is_favorite']);
            $table->index('url_hash');

            // FULLTEXT index only works on MySQL
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['title', 'description', 'ai_summary', 'url'], 'ft_search');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
