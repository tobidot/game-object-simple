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
        Schema::create('projects', function (Blueprint $table) {
            $table->id()->index();
            $table->timestamps();
            $table->string('title', 255);
            $table->string('thumbnail', 511)->nullable();
            $table->text('description');
            // publish
            $table->foreignId('publish_state_id')
                ->index()
                ->references('id')
                ->on('lu_publish_states')
                ->cascadeOnUpdate();
            // development state
            $table->foreignId('state_id')
                ->index()
                ->references('id')
                ->on('lu_project_states')
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
