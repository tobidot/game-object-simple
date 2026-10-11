<?php

namespace Tests\Feature;

use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Models\CodeRelease;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewsAndBemTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully_with_header_and_navigation_bem(): void
    {
        Page::factory([
            'uri' => 'home',
            'title' => 'Home',
            'publish_state' => PublishState::PUBLISHED,
        ])->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('class="header"', false);
        $response->assertSee('class="navigation"', false);
        $response->assertSee('class="navigation__list"', false);
        $response->assertSee('class="navigation__item"', false);
        $response->assertSee('navigation__link', false);
    }

    public function test_imprint_page_renders_with_bem_classes(): void
    {
        $response = $this->get('/imprint');

        $response->assertStatus(200);
        $response->assertSee('imprint__content', false);
        $response->assertSee('imprint__text', false);
    }

    public function test_pages_index_renders_with_pagination_and_archive_bem(): void
    {
        Page::factory()->count(10)->create([
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $response = $this->get('/pages');

        $response->assertStatus(200);
        $response->assertSee('archive archive--with-excerpts', false);
        $response->assertSee('archive__item', false);
        $response->assertSee('pagination-section', false);
        $response->assertSee('pagination-section__links', false);
        $response->assertSee('pagination-section__total', false);
        $response->assertSee('pagination-section__search', false);
        $response->assertSee('pagination__list', false);
        $response->assertSee('pagination__item', false);
        $response->assertSee('pagination__link', false);
    }

    public function test_page_show_renders_with_bem_classes_and_comments(): void
    {
        /** @var Page $page */
        $page = Page::factory([
            'uri' => 'my-test-page',
            'title' => 'My Test Page',
            'content' => '<p>Test page content</p>',
            'publish_state' => PublishState::PUBLISHED,
        ])->create();

        $relatedPage = Page::factory([
            'uri' => 'related-page',
            'title' => 'Related Page',
            'publish_state' => PublishState::PUBLISHED,
        ])->create();
        $page->pages()->attach($relatedPage);

        Comment::factory([
            'commentable_type' => Page::class,
            'commentable_id' => $page->id,
            'title' => 'First Comment',
            'author' => 'Tester',
            'content' => 'Great page!',
            'visible' => true,
        ])->create();

        $response = $this->get('/pages/my-test-page');

        $response->assertStatus(200);
        $response->assertSee('page__content', false);
        $response->assertSee('page__related', false);
        $response->assertSee('page__comments', false);
        $response->assertSee('comment-section', false);
        $response->assertSee('comment-section__title', false);
        $response->assertSee('comment-form', false);
        $response->assertSee('comment-form__row', false);
        $response->assertSee('comment-list', false);
        $response->assertSee('comment__title', false);
        $response->assertSee('First Comment', false);
    }

    public function test_projects_index_renders_with_pagination_and_archive_bem(): void
    {
        Project::factory()->count(8)->create([
            'publish_state' => PublishState::PUBLISHED,
        ]);

        $response = $this->get('/projects');

        $response->assertStatus(200);
        $response->assertSee('archive archive--with-excerpts', false);
        $response->assertSee('archive__item', false);
        $response->assertSee('pagination-section', false);
        $response->assertSee('pagination-section__links', false);
        $response->assertSee('pagination-section__total', false);
        $response->assertSee('pagination-section__search', false);
        $response->assertSee('pagination__list', false);
    }

    public function test_project_show_renders_with_releases_and_bem_classes(): void
    {
        /** @var Project $project */
        $project = Project::factory([
            'title' => 'My Test Project',
            'description' => '<p>Project description</p>',
            'publish_state' => PublishState::PUBLISHED,
            'state' => ProjectState::FINISHED,
        ])->create();

        CodeRelease::factory([
            'project_id' => $project->id,
            'version' => '1.0.0',
        ])->create();

        $response = $this->get('/projects/'.$project->id);

        $response->assertStatus(200);
        $response->assertSee('project__content', false);
        $response->assertSee('project__main-release', false);
        $response->assertSee('project__main-release-link', false);
        $response->assertSee('project__releases', false);
        $response->assertSee('project__comments', false);
        $response->assertSee('comment-section', false);
    }

    public function test_tce_generator_page_renders_successfully(): void
    {
        $response = $this->get('/tce-generator');

        $response->assertStatus(200);
        $response->assertSee('tce-generator', false);
    }
}
