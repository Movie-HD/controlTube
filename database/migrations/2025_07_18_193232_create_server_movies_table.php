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
        Schema::create('server_movies', function (Blueprint $table) {
            $table->id();
            $table->string('movie_name');
            $table->string('tmdb_id');
            $table->text('movie_link');
            $table->text('description')->nullable();
            $table->json('screenshots')->nullable();

            $table->foreignId('host_server_id')->constrained()->nullOnDelete(); # relación al host_server para llamarlo a traves del select

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_movies');
    }
};
