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
    public function up()
    {
        Schema::create('tobidot_element_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tobidot_element_id')->constrained('tobidot_elements')->cascadeOnDelete();
            $table->foreignId('dependency_id')->constrained('tobidot_elements')->cascadeOnDelete();

            // Version requirements (optional)
            $table->integer('required_major')->nullable();
            $table->integer('required_minor')->nullable();
            $table->integer('required_patch')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tobidot_element_dependencies');
    }
};
