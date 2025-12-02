<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('associated_webs', function (Blueprint $table) {
            $table->boolean('wp_edit_completed')->default(false)->after('badge_color');
            $table->string('wp_edit_url')->nullable()->after('wp_edit_completed');
        });
    }

    public function down(): void
    {
        Schema::table('associated_webs', function (Blueprint $table) {
            $table->dropColumn(['wp_edit_completed', 'wp_edit_url']);
        });
    }
};
