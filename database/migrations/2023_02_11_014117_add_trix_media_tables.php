<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('nova_pending_trix_attachments', function (Blueprint $table) {
            $table->increments('id')->index();
            $table->string('draft_id')->index();
            $table->string('attachment');
            $table->string('disk');
            $table->timestamps();
        });

        Schema::create('nova_trix_attachments', function (Blueprint $table) {
            $table->increments('id')->index();
            $table->string('attachable_type');
            $table->unsignedInteger('attachable_id');
            $table->string('attachment');
            $table->string('disk');
            $table->string('url')->index();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('nova_trix_attachments');
        Schema::drop('nova_pending_trix_attachments');
    }
};
