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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id()->index();
            $table->timestamps();
            $table->string('url', 255)->index();
            $table->string('path', 255);
            $table->foreignId('publish_state_id')
                ->index()
                ->references('id')
                ->on('lu_publish_states')
                ->cascadeOnUpdate();
            $table->foreignId('type_id')
                ->index()
                ->references('id')
                ->on('lu_attachment_types')
                ->cascadeOnUpdate();
        });
        Schema::create('attachables', function(Blueprint $table){
            $table->foreignId('attachment_id')
                ->index()
                ->references('id')
                ->on('attachments');
            $table->unsignedBigInteger('attachable_id');
            $table->string('attachable_type', 255);
            $table->string('relation', 255)->index();
            $table->index(['attachable_id', 'attachable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
