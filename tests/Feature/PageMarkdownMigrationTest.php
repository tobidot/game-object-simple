<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The migration that moved page content from Trix HTML to markdown.
 */
class PageMarkdownMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_24_120000_convert_page_content_to_markdown.php');
    }

    public function test_converts_the_pages_and_keeps_their_html(): void
    {
        // Schema changes commit implicitly in MySQL, so this row outlives the test transaction: it removes it itself.
        $migration = $this->migration();
        $migration->down();
        $html = '<h2>Hello</h2><div>first<br>second</div>';
        $id = DB::table('pages')->insertGetId([
            'uri' => 'old-page', 'title' => 'Old', 'content' => $html, 'publish_state' => 'published',
            'created_at' => '2021-01-01 00:00:00', 'updated_at' => '2021-01-02 00:00:00',
        ]);

        $migration->up();

        $page = DB::table('pages')->find($id);
        $this->assertSame("## Hello\n\nfirst\nsecond\n", $page->content);
        $this->assertSame($html, $page->content_html_legacy);
        $this->assertSame('2021-01-02 00:00:00', $page->updated_at);

        $migration->down();

        $this->assertSame($html, DB::table('pages')->find($id)->content);
        $this->assertFalse(Schema::hasColumn('pages', 'content_html_legacy'));
        $migration->up();
        DB::table('pages')->delete($id);
    }
}
