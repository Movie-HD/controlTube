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
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birth_date')->nullable();
            $table->foreignId('proxy_id')->nullable()->constrained('youtube_proxies')->nullOnDelete();
            $table->string('channel_url')->nullable();

            # Relación con el estado
            $table->foreignId('status_id')->default(1)->constrained('account_statuses')->cascadeOnDelete();
            # Relación con la resolucion
            $table->foreignId('resolution_id')->nullable()->constrained('resolutions')->nullOnDelete();

            $table->boolean('captcha_required')->default(false);
            $table->boolean('verification_pending')->default(false);
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
