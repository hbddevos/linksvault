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
        Schema::create('google_drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at');
            $table->string('root_folder_id', 100)->nullable()->comment('Default export folder');
            $table->string('email', 255)->nullable()->comment('Google account email');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drives');
    }
};
