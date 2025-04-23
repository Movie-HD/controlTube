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
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->boolean('is_physical_chip')->default(false);
            $table->string('name')->nullable();
            $table->string('dni')->nullable();
            $table->string('iccid_code')->nullable();
            $table->date('registered_at')->nullable();

            $table->boolean('in_use')->default(false);
            $table->foreignId('used_by_account_id')->nullable()->constrained('youtube_accounts')->nullOnDelete();

            # Campo codigo pais
            $table->string('phone_country')->nullable();

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
        Schema::dropIfExists('phone_numbers');
    }
};
