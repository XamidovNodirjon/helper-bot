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
        // 1. Add language column to telegram_users table
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->string('language')->default('uz')->after('username');
        });

        // 2. Create seen_listings table to prevent repeating listings
        Schema::create('seen_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->string('url')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. Create saved_searches table to store up to 3 filter configurations per user
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->string('name');
            $table->string('category');
            $table->string('region');
            $table->string('district')->nullable();
            $table->text('filters')->nullable(); // JSON configuration
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('seen_listings');
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
