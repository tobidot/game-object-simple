<?php

use App\Services\MarkdownService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Page content moves from Trix HTML to markdown. The HTML stays in content_html_legacy until a
 * later release drops it, so a conversion can be checked and redone.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('content_html_legacy')->nullable()->after('content');
        });

        $markdown = resolve(MarkdownService::class);
        DB::table('pages')->orderBy('id')->each(function (object $page) use ($markdown) {
            DB::table('pages')->where('id', $page->id)->update([
                'content_html_legacy' => $page->content,
                'content' => $markdown->fromTrixHtml((string) $page->content),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pages')->whereNotNull('content_html_legacy')->update(['content' => DB::raw('content_html_legacy')]);
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('content_html_legacy');
        });
    }
};
