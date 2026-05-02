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
        Schema::table('tobidot_elements', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['content', 'icon']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tobidot_elements', function (Blueprint $blueprint) {
            $blueprint->string('content')->nullable();
            $blueprint->string('icon')->nullable();
        });
    }
};
