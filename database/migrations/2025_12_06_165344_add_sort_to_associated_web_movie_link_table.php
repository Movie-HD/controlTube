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
        Schema::table('associated_web_movie_link', function (Blueprint $table) {
            $table->unsignedInteger('sort')->default(0)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('associated_web_movie_link', function (Blueprint $table) {
            $table->dropColumn('sort');
        });
    }
};
