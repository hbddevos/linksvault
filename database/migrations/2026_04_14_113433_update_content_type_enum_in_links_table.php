<?php

use App\Enums\ContentType;
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
        // Migrate existing data from 'drive' to 'google_drive'
        DB::table('links')
            ->where('content_type', 'drive')
            ->update(['content_type' => ContentType::GoogleDrive->value]);

        Schema::table('links', function (Blueprint $table) {
            $table->string('content_type')
                ->default(ContentType::Other->value)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->enum('content_type', ['youtube', 'drive', 'article', 'pdf', 'image', 'other'])
                ->default('other')
                ->change();
        });

        // Migrate data back
        DB::table('links')
            ->where('content_type', ContentType::GoogleDrive->value)
            ->update(['content_type' => 'drive']);
    }
};
