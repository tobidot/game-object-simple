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
        Schema::table('tobidot_elements', function (Blueprint $table) {
            $table->dropForeign(['icon_attachment_id']);
            $table->dropColumn('icon_attachment_id');
            $table->dropForeign(['attachment_id']);
            $table->dropColumn('attachment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tobidot_elements', function (Blueprint $table) {
            $table->foreignId('icon_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
            $table->foreignId('attachment_id')
                ->nullable()
                ->constrained()
                ->references('id')
                ->on('attachments');
        });
    }
};
