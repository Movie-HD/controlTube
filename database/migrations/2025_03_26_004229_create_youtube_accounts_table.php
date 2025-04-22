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
        Schema::create('youtube_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birth_date')->nullable();
            $table->foreignId('proxy_id')->nullable()->constrained('youtube_proxies')->nullOnDelete();
            $table->string('channel_url')->nullable();

            # Relación con phone_number
            $table->foreignId('phone_number_id')->nullable()->constrained('phone_numbers')->nullOnDelete();
            # Relación con el estado
            $table->foreignId('status_id')->nullable()->constrained('account_statuses')->cascadeOnDelete();
            # Relación con la resolucion
            $table->foreignId('resolution_id')->nullable()->constrained('resolutions')->nullOnDelete();

            $table->boolean('captcha_required')->nullable();
            $table->boolean('verification_pending')->nullable();

            # Campos de Horaio de Actividad
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            # Campo screenshots upload
            $table->string('descripcion')->nullable();
            $table->json('screenshots')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_accounts');
    }
};
