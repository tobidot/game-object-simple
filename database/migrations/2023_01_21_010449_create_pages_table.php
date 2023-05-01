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
        Schema::create('pages', function (Blueprint $table) {
            $table->id()->index();
            $table->timestamps();
            $table->string('uri', 255)->index();
            $table->string('title', 511);
            $table->longText('content');
            $table->string('thumbnail', 511)->nullable();
            $table->foreignId('publish_state_id')
                ->index()
                ->references('id')
                ->on('lu_publish_states')
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists('pages');
    }
};
