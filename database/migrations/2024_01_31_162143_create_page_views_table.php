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
    public function up() : void
    {
        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('url' , 2048);
            $table->nullableMorphs('viewable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists('views');
    }
};
