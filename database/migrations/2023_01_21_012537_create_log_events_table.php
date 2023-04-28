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
        Schema::create('log_events', function (Blueprint $table) {
            $table->id()->index();
            $table->timestamps();
            $table->foreignId('type_id')
                ->index()
                ->references('id')
                ->on('lu_log_event_types')
                ->cascadeOnUpdate();
            $table->unsignedBigInteger('loggable_id');
            $table->string('loggable_type', 255);
            $table->index(['loggable_id', 'loggable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('log_events');
    }
};
