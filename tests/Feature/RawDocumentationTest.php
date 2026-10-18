<?php

namespace Tests\Feature;

use Tests\TestCase;

class RawDocumentationTest extends TestCase
{
    public function test_serves_markdown_from_the_docs_folder(): void
    {
        $this->get('/documentation/features/tce-framework/coding-style.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Coding Style', false);

        $this->get('/documentation')->assertOk();
    }

    public function test_refuses_anything_outside_the_docs_folder_or_not_markdown(): void
    {
        $this->get('/documentation/..%2F..%2F..%2F.env')->assertNotFound();
        $this->get('/documentation/features')->assertNotFound();
        $this->get('/documentation/missing.md')->assertNotFound();
    }
}
