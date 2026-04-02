<?php

namespace Tests\Feature;

use App\Models\TobidotElement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TobidotElementUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_tobidot_element()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $zip = UploadedFile::fake()->create('element.zip', 100);

        $response = $this->postJson(route('tobidot-elements.upload'), [
            'name' => 'test-widget',
            'zip' => $zip,
            'version' => '1.0.0',
            'kind' => 'widget',
            'description' => 'A test widget',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tobidot element uploaded successfully',
            ]);

        $this->assertDatabaseHas('tobidot_elements', [
            'name' => 'test-widget',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'kind' => 'widget',
        ]);

        $element = TobidotElement::where('name', 'test-widget')->first();
        $this->assertNotNull($element->attachment_id);
        $this->assertNotNull($element->content);

        // Check if files were "extracted" (Storage::fake doesn't actually extract,
        // but AttachmentService will try to call ZipArchive, which might fail on a fake file if not careful.
        // Actually ZipArchive works on local paths. UploadedFile::fake() creates a real temp file.
    }

    public function test_upload_increments_version_automatically()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // First upload
        $this->postJson(route('tobidot-elements.upload'), [
            'name' => 'auto-widget',
            'zip' => UploadedFile::fake()->create('element1.zip', 10),
            'version' => '0.1.0',
        ]);

        // Second upload without version
        $response = $this->postJson(route('tobidot-elements.upload'), [
            'name' => 'auto-widget',
            'zip' => UploadedFile::fake()->create('element2.zip', 10),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tobidot_elements', [
            'name' => 'auto-widget',
            'major' => 0,
            'minor' => 1,
            'patch' => 1,
        ]);
    }
}
