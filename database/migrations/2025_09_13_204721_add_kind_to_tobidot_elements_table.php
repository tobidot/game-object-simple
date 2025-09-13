<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tobidot_elements', function (Blueprint $table) {
            $table->string('kind')->default('element');
            $table->index('name');
            $table->index(['name', 'major', 'minor', 'patch']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tobidot_elements', function (Blueprint $table) {
            $table->dropColumn('kind');
            $table->dropIndex('name');
            $table->dropIndex(['name', 'major', 'minor', 'patch']);
        });
    }
};
