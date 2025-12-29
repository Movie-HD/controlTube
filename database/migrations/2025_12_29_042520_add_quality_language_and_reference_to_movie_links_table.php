<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movie_links', function (Blueprint $table) {
            $table->string('calidad')->nullable()->after('movie_link');
            $table->string('idioma')->nullable()->after('calidad');
            $table->string('referencia')->nullable()->after('idioma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_links', function (Blueprint $table) {
            $table->dropColumn(['calidad', 'idioma', 'referencia']);
        });
    }
};
