<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onlyfans_profiles', function (Blueprint $table) {
            $table->string('multilogin_profile_id')->nullable()->after('username');
            $table->timestamp('last_browser_session')->nullable()->after('last_scraped_at');
            $table->json('browser_cookies')->nullable()->after('last_browser_session');
        });
    }

    public function down(): void
    {
        Schema::table('onlyfans_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'multilogin_profile_id',
                'last_browser_session',
                'browser_cookies'
            ]);
        });
    }
};