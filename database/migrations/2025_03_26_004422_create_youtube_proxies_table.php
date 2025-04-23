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
        Schema::create('youtube_proxies', function (Blueprint $table) {
            $table->id();
            $table->string('proxy');
            $table->boolean('in_use')->default(false); // Indica si está en uso
            $table->foreignId('used_by_account_id')->nullable()->constrained('youtube_accounts')->nullOnDelete();

            # Campo descripcion + screenshots upload
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
        Schema::dropIfExists('youtube_proxies');
    }
};
