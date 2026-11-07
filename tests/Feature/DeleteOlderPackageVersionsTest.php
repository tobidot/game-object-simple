<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Page;
use App\Models\TcePackage;
use App\Models\User;
use App\Services\Models\TcePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteOlderPackageVersionsTest extends TestCase
{
    use RefreshDatabase;

    private function version(string $name, int $major, int $minor = 0, string $namespace = 'tobidot'): TcePackage
    {
        return TcePackage::query()->create([
            'namespace' => $namespace,
            'name' => $name,
            'kind' => 'element',
            'major' => $major,
            'minor' => $minor,
            'patch' => 0,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function versionsLeft(string $name): array
    {
        return TcePackage::query()
            ->where('name', $name)
            ->orderBy('major')
            ->orderBy('minor')
            ->get()
            ->map(fn (TcePackage $package): string => "$package->major.$package->minor")
            ->all();
    }

    private function depend(TcePackage $package, TcePackage $on, ?int $required_major = null): void
    {
        DB::table('tce_package_dependencies')->insert([
            'tce_package_id' => $package->id,
            'dependency_id' => $on->id,
            'required_major' => $required_major,
        ]);
    }

    private function service(): TcePackageService
    {
        return resolve(TcePackageService::class);
    }

    public function test_only_the_newest_stays_whichever_version_was_selected(): void
    {
        $this->version('tce-a', 1);
        $selected = $this->version('tce-a', 1, 10);
        $this->version('tce-a', 1, 2);
        $this->version('tce-a', 2);
        $this->version('tce-other', 1);

        $result = $this->service()->deleteOlderVersions($selected);

        $this->assertSame(3, $result['deleted']);
        $this->assertSame(['2.0'], $this->versionsLeft('tce-a'));
        $this->assertSame(['1.0'], $this->versionsLeft('tce-other'));
    }

    public function test_keep_counts_from_the_newest_and_never_drops_to_zero(): void
    {
        $this->version('tce-a', 1);
        $this->version('tce-a', 2);
        $newest = $this->version('tce-a', 3);

        $this->service()->deleteOlderVersions($newest, 2);
        $this->assertSame(['2.0', '3.0'], $this->versionsLeft('tce-a'));

        $this->service()->deleteOlderVersions($newest, 0);
        $this->assertSame(['3.0'], $this->versionsLeft('tce-a'));
    }

    public function test_the_same_name_in_another_namespace_is_a_different_package(): void
    {
        $this->version('tce-a', 1, 0, 'someone');
        $this->version('tce-a', 1);
        $newest = $this->version('tce-a', 2);

        $this->service()->deleteOlderVersions($newest);

        $this->assertSame(['1.0', '2.0'], $this->versionsLeft('tce-a'));
    }

    public function test_dependents_move_to_the_newest_version(): void
    {
        $old = $this->version('tce-library', 1);
        $newest = $this->version('tce-library', 2);
        $dependent = $this->version('tce-dependent', 1);
        $this->depend($dependent, $old);

        $this->service()->deleteOlderVersions($newest);

        $this->assertSame(['2.0'], $this->versionsLeft('tce-library'));
        $this->assertDatabaseHas('tce_package_dependencies', [
            'tce_package_id' => $dependent->id,
            'dependency_id' => $newest->id,
        ]);
        $this->assertDatabaseCount('tce_package_dependencies', 1);
    }

    public function test_a_pinned_version_stays_and_is_reported(): void
    {
        $pinned = $this->version('tce-library', 1);
        $this->version('tce-library', 1, 5);
        $newest = $this->version('tce-library', 2);
        $this->depend($this->version('tce-dependent', 1), $pinned, 1);

        $result = $this->service()->deleteOlderVersions($newest);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(['tce-library@1.0.0'], $result['still_needed']);
        $this->assertSame(['1.0', '2.0'], $this->versionsLeft('tce-library'));
    }

    public function test_a_version_a_page_embeds_stays(): void
    {
        $this->version('tce-a', 1);
        $this->version('tce-a', 1, 5);
        $newest = $this->version('tce-a', 2);
        Page::factory()->create(['content' => 'Look: /tce-elements/tobidot/tce-a@1.0.0', 'publish_state' => 'private']);

        $result = $this->service()->deleteOlderVersions($newest);

        $this->assertSame(['tce-a@1.0.0'], $result['still_needed']);
        $this->assertSame(['1.0', '2.0'], $this->versionsLeft('tce-a'));
    }

    public function test_links_go_but_a_shared_icon_stays_with_the_kept_version(): void
    {
        $old = $this->version('tce-a', 1);
        $newest = $this->version('tce-a', 2);
        $code = Attachment::factory()->create(['path' => '/tce-packages/old', 'url' => '/storage/tce-packages/old']);
        $icon = Attachment::factory()->create(['path' => '/media/icon', 'url' => '/storage/media/icon']);
        $old->attachments()->attach($code->id, ['relation' => 'code']);
        $old->attachments()->attach($icon->id, ['relation' => 'icon']);
        $newest->attachments()->attach($icon->id, ['relation' => 'icon']);

        $this->service()->deleteOlderVersions($newest);

        $this->assertDatabaseMissing('attachables', ['attachment_id' => $code->id]);
        $this->assertDatabaseHas('attachables', ['attachment_id' => $icon->id, 'attachable_id' => $newest->id]);
        $this->assertDatabaseHas('attachments', ['id' => $code->id]);
    }

    public function test_a_plain_delete_moves_dependents_too(): void
    {
        $old = $this->version('tce-library', 1);
        $newest = $this->version('tce-library', 2);
        $dependent = $this->version('tce-dependent', 1);
        $this->depend($dependent, $newest);

        $newest->delete();

        $this->assertDatabaseHas('tce_package_dependencies', [
            'tce_package_id' => $dependent->id,
            'dependency_id' => $old->id,
        ]);
    }

    public function test_the_nova_action_works_from_a_selection(): void
    {
        $this->version('tce-a', 1);
        $selected = $this->version('tce-a', 2);
        $this->version('tce-a', 3);
        $other = $this->version('tce-b', 1);
        $this->version('tce-b', 2);

        $response = $this->actingAs(User::factory()->create(['email' => 'object.name@live.de']))
            ->postJson('/nova-api/tce-packages/action?action=delete-older-versions', [
                'resources' => [$selected->id, $other->id],
                'keep' => 1,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('3 older versions deleted', $response->json('message'));
        $this->assertSame(['3.0'], $this->versionsLeft('tce-a'));
        $this->assertSame(['2.0'], $this->versionsLeft('tce-b'));
    }

    public function test_the_filter_lists_one_row_per_package_with_older_versions(): void
    {
        $this->version('tce-a', 1);
        $newest = $this->version('tce-a', 1, 10);
        $this->version('tce-a', 1, 9);
        $this->version('tce-single', 1);

        $filter = base64_encode(json_encode([
            ['App\\Nova\\Filters\\TcePackagesWithOlderVersions' => ['has_older' => true]],
        ]));
        $response = $this->actingAs(User::factory()->create(['email' => 'object.name@live.de']))
            ->getJson('/nova-api/tce-packages?filters='.$filter);

        $response->assertOk();
        $this->assertSame([$newest->id], array_map(
            fn (array $resource): int => $resource['id']['value'],
            $response->json('resources'),
        ));
    }

    public function test_the_last_version_with_dependents_cannot_be_deleted_in_nova(): void
    {
        $library = $this->version('tce-library', 1);
        $this->depend($this->version('tce-dependent', 1), $library);

        $this->actingAs(User::factory()->create(['email' => 'object.name@live.de']))
            ->deleteJson('/nova-api/tce-packages?resources[]='.$library->id);

        $this->assertDatabaseHas('tce_packages', ['id' => $library->id]);
    }
}
