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
        Schema::create('movie_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('host_server_id')->constrained()->onDelete('cascade');
            $table->text('movie_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_links');
    }
};
