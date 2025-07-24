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
        Schema::create('associated_webs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_movie_id')->constrained()->onDelete('cascade');
            $table->string('link')->nullable();
            $table->string('get_domain')->nullable();
            $table->string('badge_color')->nullable();
            $table->text('description')->nullable();
            $table->json('screenshots')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('associated_webs');
    }
};
