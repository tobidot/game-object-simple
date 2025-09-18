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
            $table->foreignId('attachment_id')
                ->nullable()
                ->constrained()
                ->references('id')
                ->on('attachments')
            ;
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
            $table->dropForeign(['attachment_id']);
            $table->dropColumn('attachment_id');
        });
    }
};
