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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 255)->nullable()->after('email_verified_at');
            $table->string('github_id', 255)->nullable()->after('google_id');
            $table->string('avatar', 500)->nullable()->after('github_id');
            $table->string('locale', 10)->default('en')->after('avatar');
            $table->timestamp('onboarding_completed_at')->nullable()->after('locale');
            $table->string('timezone', 50)->default('UTC')->after('onboarding_completed_at');
            $table->json('preferences')->nullable()->after('timezone')->comment('User preferences (theme, shortcuts, etc)');

            $table->index('google_id');
            $table->index('github_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['github_id']);
            $table->dropColumn(['google_id', 'github_id', 'avatar', 'locale', 'onboarding_completed_at', 'timezone', 'preferences']);
        });
    }
};
