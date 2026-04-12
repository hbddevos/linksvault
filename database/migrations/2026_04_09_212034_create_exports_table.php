<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->enum('format', ['csv', 'xlsx']);
            $table->string('file_path', 500)->nullable()->comment('Local storage path');
            $table->bigInteger('file_size')->nullable()->comment('Bytes');
            $table->string('download_url', 500)->nullable()->comment('Temporary download URL');
            $table->string('drive_file_id', 255)->nullable()->comment('Google Drive file ID');
            $table->string('drive_url', 500)->nullable()->comment('Google Drive share URL');
            $table->integer('link_count')->nullable()->comment('Number of links exported');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Download URL expiry');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
