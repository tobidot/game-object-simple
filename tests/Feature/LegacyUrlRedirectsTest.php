<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The URLs of the removed projects and code releases stay alive as permanent redirects.
 */
class LegacyUrlRedirectsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function redirects(): array
    {
        return [
            'archive' => ['/projects', '/pages'],
            'to a page' => ['/projects/1', '/pages/ant-colony'],
            'to a nested page' => ['/projects/20', '/pages/spielwerk/15-memory'],
            'to a package' => ['/projects/15', '/tce/tce-yesdevs-shared'],
            'to home' => ['/projects/18', '/'],
            'to the archive' => ['/projects/13', '/pages'],
            'proxy' => ['/projects/34/proxy', '/pages/spielwerk/03-tic-tac-toe'],
            'proxy path' => ['/projects/9/proxy/index.html', '/pages/spielwerk/18-rundenkampf'],
            'deep proxy path' => ['/projects/21/proxy/assets/game.js', '/pages/spielwerk/18-rundenkampf'],
            'unknown id' => ['/projects/999', '/pages'],
            'unknown id with proxy' => ['/projects/999/proxy/index.html', '/pages'],
            'zero' => ['/projects/0', '/pages'],
            'code release' => ['/code-releases/12', '/pages'],
            'code release proxy' => ['/code-releases/12/proxy', '/pages'],
            'code release proxy path' => ['/code-releases/12/proxy/assets/game.js', '/pages'],
        ];
    }

    #[DataProvider('redirects')]
    public function test_old_url_redirects_permanently(string $old_url, string $target): void
    {
        $response = $this->get($old_url);

        $response->assertStatus(301);
        $response->assertRedirect($target);
    }

    public function test_other_paths_below_a_project_are_not_found(): void
    {
        $this->get('/projects/1/something-else')->assertNotFound();
    }

    public function test_the_v3_embed_urls_still_route(): void
    {
        $this->assertSame('tce-elements.embed', app('router')->getRoutes()->match(request()->create('/tce-elements/tobidot/tce-clock'))->getName());
        $this->assertSame('tce-elements.embed.default', app('router')->getRoutes()->match(request()->create('/tce-elements/tce-clock'))->getName());

        // Routed to the controller, which then finds no such package.
        $this->get('/tce-elements/tobidot/tce-does-not-exist')->assertNotFound();
    }

    public function test_play_url_of_a_name_without_a_package_is_not_found(): void
    {
        $this->get('/tce/tce-does-not-exist')->assertNotFound();
    }
}
