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
        Schema::create('code_releases', function (Blueprint $table) {
            $table->id()->index();
            $table->timestamps();
            $table->string('version', 63)->index()->default('0.0.1');
            $table->float('completeness')->default(0);
            $table->float('fun')->default(0);
            $table->float('complexity')->default(0);
            $table->foreignId('project_id')
                ->index()
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists('code_releases');
    }
};
