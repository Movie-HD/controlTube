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
        Schema::create('youtube_account_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youtube_account_id')->constrained()->cascadeOnDelete(); // Relación con la cuenta de YouTube
            $table->foreignId('youtube_page_id')->constrained()->cascadeOnDelete(); // Relación con la página externa (Facebook, etc.)
            $table->string('email')->nullable(); // Email usado para el registro en la página
            $table->string('password')->nullable(); // Contraseña (en un sistema real, considerar encriptación)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_account_pages');
    }
};
