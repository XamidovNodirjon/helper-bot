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
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique()->index();
            $table->string('username')->nullable();
            $table->string('step')->default('arenda_type');
            $table->string('arenda_type')->nullable();
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->integer('area_min')->nullable();
            $table->integer('area_max')->nullable();
            $table->string('price_currency')->nullable();
            $table->integer('price_min')->nullable();
            $table->integer('price_max')->nullable();
            $table->integer('current_page')->default(1);
            $table->longText('last_results')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
