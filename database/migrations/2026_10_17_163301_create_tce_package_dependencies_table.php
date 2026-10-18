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
        Schema::create('tce_package_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tce_package_id')->constrained('tce_packages')->cascadeOnDelete();
            $table->foreignId('dependency_id')->constrained('tce_packages')->cascadeOnDelete();

            // Version requirements (optional) — caret-range constraints, see "Package Model &
            // Versioning: Version Matching Is Caret-Range".
            $table->integer('required_major')->nullable();
            $table->integer('required_minor')->nullable();
            $table->integer('required_patch')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tce_package_dependencies');
    }
};
