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
            $table->string('brand')->nullable()->after('district');
            $table->string('condition')->nullable()->after('brand');
            $table->string('transmission')->nullable()->after('condition');
            $table->string('fuel_type')->nullable()->after('transmission');
            $table->integer('year_min')->nullable()->after('fuel_type');
            $table->integer('year_max')->nullable()->after('year_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn(['brand', 'condition', 'transmission', 'fuel_type', 'year_min', 'year_max']);
        });
    }
};
