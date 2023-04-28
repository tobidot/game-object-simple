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
        Schema::create('lu_project_states', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name', 63)->index();
            $table->string('label', 255)->nullable();
        });
        Schema::create('lu_publish_states', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name', 63)->index();
            $table->string('label', 255)->nullable();
        });
        Schema::create('lu_attachment_types', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name', 63)->index();
            $table->string('label', 255)->nullable();
        });
        Schema::create('lu_log_event_types', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name', 63)->index();
            $table->string('label', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists('lu_publish_states');
        Schema::dropIfExists('lu_project_states');
    }
};
