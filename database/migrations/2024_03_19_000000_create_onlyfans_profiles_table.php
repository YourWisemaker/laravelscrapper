<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onlyfans_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name')->nullable();
            $table->text('bio')->nullable();
            $table->integer('likes_count')->default(0);
            $table->string('avatar_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->integer('posts_count')->default(0);
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
            
            // Enable fulltext search for MySQL
            $table->fullText(['username', 'name', 'bio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onlyfans_profiles');
    }
};