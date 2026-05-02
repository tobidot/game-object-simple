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
        Schema::table('tobidot_elements', function (Blueprint $blueprint) {
            $blueprint->foreignId('icon_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tobidot_elements', function (Blueprint $blueprint) {
            $blueprint->dropConstrainedForeignId('icon_attachment_id');
        });
    }
};
