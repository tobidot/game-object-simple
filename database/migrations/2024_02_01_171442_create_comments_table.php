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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean("visible")->default(true)->index();
            $table->string("author", 64)->nullable()->index();
            $table->string("email", 255)->nullable()->index();
            $table->string("title", 128)->nullable()->index();
            $table->string("content", 2048);
            $table->morphs("commentable");
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->boolean("allow_comments")->default(true);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->boolean("allow_comments")->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn("allow_comments");
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("allow_comments");
        });
        Schema::dropIfExists('comments');
    }
};
