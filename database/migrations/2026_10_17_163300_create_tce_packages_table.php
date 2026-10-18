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
        Schema::create('tce_packages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('namespace', 255);
            $table->string('name', 255);
            $table->string('kind')->default('element');
            $table->text('description')->nullable();
            $table->integer('major');
            $table->integer('minor');
            $table->integer('patch');
            $table->integer('width')->default(200);
            $table->integer('height')->default(200);
            $table->text('extra')->nullable();

            $table->index(['namespace', 'name']);
            $table->index(['namespace', 'name', 'major', 'minor', 'patch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tce_packages');
    }
};
