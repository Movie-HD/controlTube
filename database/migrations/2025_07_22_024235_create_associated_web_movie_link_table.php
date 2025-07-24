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
        Schema::create('associated_web_movie_link', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associated_web_id')->constrained()->onDelete('cascade');
            $table->foreignId('movie_link_id')->constrained()->onDelete('cascade');
            $table->boolean('was_updated')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('associated_web_movie_link');
    }
};
