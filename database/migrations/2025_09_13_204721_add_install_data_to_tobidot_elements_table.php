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
            $table->boolean('standalone')->default(true);
            $table->integer('width')->default(200);
            $table->integer('height')->default(200);
            $table->text('extra')->nullable();
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
            $table->dropColumn('standalone');
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('extra');
        });
    }
};
