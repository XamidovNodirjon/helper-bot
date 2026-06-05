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
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->after('filters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropColumn('is_subscribed');
        });
    }
};
