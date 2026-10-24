<?php

namespace Tests\Unit;

use App\Services\MarkdownService;
use PHPUnit\Framework\TestCase;

/**
 * Page content: rendering markdown, and converting what the former Trix editor stored. The Trix
 * samples follow the HTML of the pages that were converted.
 */
class MarkdownServiceTest extends TestCase
{
    private function service(): MarkdownService
    {
        return new MarkdownService;
    }

    public function test_renders_github_flavored_markdown(): void
    {
        $html = $this->service()->toHtml("| a | b |\n| - | - |\n| 1 | 2 |\n\n~~gone~~ https://example.com");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<del>gone</del>', $html);
        $this->assertStringContainsString('<a href="https://example.com">https://example.com</a>', $html);
    }

    public function test_a_line_break_stays_a_line_break(): void
    {
        $this->assertSame("<p>one<br>\ntwo</p>\n<p>three</p>\n", $this->service()->toHtml("one\ntwo\n\nthree"));
    }

    public function test_the_page_title_stays_the_only_h1(): void
    {
        $this->assertSame("<h2>Intro</h2>\n<h3>Detail</h3>\n", $this->service()->toHtml("# Intro\n### Detail"));
    }

    public function test_raw_html_is_kept_but_unsafe_links_are_not(): void
    {
        $html = $this->service()->toHtml("<audio controls src=\"/a.mp3\"></audio>\n\n[click](javascript:alert(1))");

        $this->assertStringContainsString('<audio controls src="/a.mp3"></audio>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_trix_divs_become_paragraphs_with_their_line_breaks(): void
    {
        $trix = '<h2>Title</h2><div><br>first line,<br>second line.&nbsp;<br>&nbsp;<br>Next paragraph with <strong>bold</strong> and <a href="https://example.com">a link</a>.</div><div>Jump&amp;Run&nbsp;</div>';

        $this->assertSame(
            "## Title\n\nfirst line,\nsecond line.\n\nNext paragraph with **bold** and [a link](https://example.com).\n\nJump&Run\n",
            $this->service()->fromTrixHtml($trix),
        );
    }

    public function test_trix_headings_lists_and_code(): void
    {
        $trix = '<h1>Top</h1><ol><li>one</li><li>two</li></ol><div>Run:&nbsp;</div><pre>git clone &lt;&lt;url&gt;&gt;</pre>';

        $this->assertSame(
            "## Top\n\n1. one\n2. two\n\nRun:\n\n```\ngit clone <<url>>\n```\n",
            $this->service()->fromTrixHtml($trix),
        );
    }

    public function test_trix_image_attachments_become_images_and_keep_an_edited_caption(): void
    {
        $figure = fn (string $attributes, string $caption) => '<figure data-trix-attachment="{&quot;contentType&quot;:&quot;image/png&quot;,&quot;filename&quot;:&quot;my_image.png&quot;,&quot;url&quot;:&quot;https://example.com/media/a.png&quot;}"'.$attributes.' class="attachment attachment--preview"><a href="https://example.com/media/a.png"><img src="https://example.com/media/a.png" width="10" height="10"><figcaption class="attachment__caption">'.$caption.'</figcaption></a></figure>';

        $this->assertSame(
            "![my\\_image](https://example.com/media/a.png)\n",
            $this->service()->fromTrixHtml('<div>'.$figure('', '<span class="attachment__name">my_image.png</span> <span class="attachment__size">1 KB</span>').'</div>'),
        );
        $this->assertSame(
            "![The Rules](https://example.com/media/a.png)\n*The Rules*\n",
            $this->service()->fromTrixHtml('<div>'.$figure(' data-trix-attributes="{&quot;caption&quot;:&quot;The Rules&quot;}"', 'The Rules').'</div>'),
        );
    }

    public function test_trix_file_attachments_become_links(): void
    {
        $trix = '<div><figure data-trix-attachment="{&quot;contentType&quot;:&quot;audio/mpeg&quot;,&quot;filename&quot;:&quot;Song.mp3&quot;,&quot;url&quot;:&quot;https://example.com/media/s.mp3&quot;}" class="attachment attachment--file"><a href="https://example.com/media/s.mp3"><figcaption class="attachment__caption"><span class="attachment__name">Song.mp3</span></figcaption></a></figure></div>';

        $this->assertSame("[Song.mp3](https://example.com/media/s.mp3)\n", $this->service()->fromTrixHtml($trix));
    }

    public function test_converted_trix_renders_to_the_same_text(): void
    {
        $markdown = $this->service()->fromTrixHtml('<div>a<br>b<br>&nbsp;<br>c</div>');

        $this->assertSame("<p>a<br>\nb</p>\n<p>c</p>\n", $this->service()->toHtml($markdown));
    }
}
