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
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->after('last_results');
            $table->boolean('is_banned')->default(false)->after('is_subscribed');
            $table->timestamp('subscription_expires_at')->nullable()->after('is_banned');
        });

        Schema::create('telegram_user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->string('action')->index();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user_logs');

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn(['is_subscribed', 'is_banned', 'subscription_expires_at']);
        });
    }
};
