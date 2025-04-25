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
        Schema::create('navigation_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('icon')->default('heroicon-o-link');
            $table->integer('sort_order')->default(0);
            $table->boolean('open_in_new_tab')->default(true);
            $table->boolean('is_active')->default(true);

            # Group relation
            $table->foreignId('group_id')->nullable()->constrained('navigation_groups')->nullOnDelete();

            # Email + Password
            $table->string('email')->nullable();
            $table->string('password')->nullable();

            # Descripcion + Screenshots
            $table->text('descripcion')->nullable();
            $table->json('screenshots')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_links');
    }
};
