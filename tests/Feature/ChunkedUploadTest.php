<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Nova\Actions\UploadAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;
use Tobidot\ChunkedUpload\ChunkedFile;
use Tobidot\ChunkedUpload\ChunkManager;

class ChunkedUploadTest extends TestCase
{
    use RefreshDatabase;

    protected string $testChunksDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testChunksDir = storage_path('app/testing_chunks');
        if (File::isDirectory($this->testChunksDir)) {
            File::deleteDirectory($this->testChunksDir);
        }
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testChunksDir)) {
            File::deleteDirectory($this->testChunksDir);
        }
        parent::tearDown();
    }

    public function test_chunk_manager_saves_and_assembles_chunks(): void
    {
        $manager = new ChunkManager($this->testChunksDir);
        $uploadId = 'test_upload_123';
        $fileName = 'archive_game.zip';

        // Part 1
        $part1Content = 'CHUNK_PART_1_DATA_ABC';
        $part1File = UploadedFile::fake()->createWithContent('chunk_0', $part1Content);
        $manager->saveChunk($uploadId, 0, $part1File);

        $this->assertFalse($manager->hasAllChunks($uploadId, 2));

        // Part 2
        $part2Content = 'CHUNK_PART_2_DATA_XYZ';
        $part2File = UploadedFile::fake()->createWithContent('chunk_1', $part2Content);
        $manager->saveChunk($uploadId, 1, $part2File);

        $this->assertTrue($manager->hasAllChunks($uploadId, 2));

        // Assemble
        $assembledPath = $manager->assembleChunks($uploadId, 2, $fileName);
        $this->assertFileExists($assembledPath);
        $this->assertSame($part1Content.$part2Content, file_get_contents($assembledPath));

        // Create UploadedFile
        $uploadedFile = $manager->createUploadedFile($uploadId, $fileName);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertSame($fileName, $uploadedFile->getClientOriginalName());
        $this->assertSame(strlen($part1Content.$part2Content), $uploadedFile->getSize());

        // Cleanup
        $dir = $this->testChunksDir.'/'.$uploadId;
        $manager->cleanupUpload($uploadId);
        $this->assertFalse(File::isDirectory($dir));
    }

    public function test_chunk_manager_cleans_up_old_abandoned_uploads(): void
    {
        $manager = new ChunkManager($this->testChunksDir);
        $oldUploadId = 'abandoned_session_old';
        $oldDir = $manager->getUploadDir($oldUploadId);
        File::put($oldDir.'/chunk_0', 'abandoned content');

        // Touch to 2 days ago
        touch($oldDir, time() - (2 * 86400));

        $deleted = $manager->cleanupOldUploads(86400);
        $this->assertSame(1, $deleted);
        $this->assertFalse(File::isDirectory($oldDir));
    }

    public function test_chunked_upload_controller_endpoints(): void
    {
        $user = User::factory()->create(['email' => 'object.name@live.de']);
        $this->actingAs($user);

        $uploadId = 'session_test_456';
        $fileName = 'build.zip';

        // Upload chunk 1 of 2
        $chunk1 = UploadedFile::fake()->createWithContent('blob', 'FIRST_HALF_OF_FILE');
        $response1 = $this->postJson(route('chunked-upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file_name' => $fileName,
            'chunk' => $chunk1,
        ]);

        $response1->assertOk()
            ->assertJson([
                'status' => 'chunk_uploaded',
                'upload_id' => $uploadId,
                'chunk_index' => 0,
                'total_chunks' => 2,
            ]);

        // Upload chunk 2 of 2 (triggers completion)
        $chunk2 = UploadedFile::fake()->createWithContent('blob', 'SECOND_HALF_OF_FILE');
        $response2 = $this->postJson(route('chunked-upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'file_name' => $fileName,
            'chunk' => $chunk2,
        ]);

        $response2->assertOk()
            ->assertJson([
                'status' => 'completed',
                'upload_id' => $uploadId,
                'file_name' => $fileName,
                'size' => strlen('FIRST_HALF_OF_FILESECOND_HALF_OF_FILE'),
            ]);

        // Test cancel/cleanup endpoint
        $cancelResponse = $this->postJson(route('chunked-upload.cancel'), [
            'upload_id' => $uploadId,
        ]);
        $cancelResponse->assertOk()->assertJson(['status' => 'cancelled']);
    }

    public function test_unauthorized_user_cannot_upload_chunks(): void
    {
        $unauth = User::factory()->create(['email' => 'regular_user@example.com']);
        $this->actingAs($unauth);

        $response = $this->postJson(route('chunked-upload.chunk'), [
            'upload_id' => 'unauth_test',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'secret.zip',
            'chunk' => UploadedFile::fake()->create('secret.zip', 10),
        ]);

        $response->assertForbidden();
    }

    public function test_chunked_file_field_fills_action_with_chunked_payload(): void
    {
        Storage::fake('public');

        /** @var ChunkManager $chunkManager */
        $chunkManager = app(ChunkManager::class);
        $uploadId = 'action_test_789';
        $fileName = 'large_game.zip';
        $content = 'SOME_COMPRESSED_BINARY_DATA';

        $chunk = UploadedFile::fake()->createWithContent('chunk_0', $content);
        $chunkManager->saveChunk($uploadId, 0, $chunk);
        $chunkManager->assembleChunks($uploadId, 1, $fileName);

        $page = Page::factory()->create(['title' => 'Test Page', 'uri' => 'test-page']);

        // Execute UploadAttachment action using the chunked upload JSON payload
        $action = new UploadAttachment;
        $fields = new ActionFields(collect([
            'file' => $chunkManager->createUploadedFile($uploadId, $fileName),
            'prefix' => 'media',
            'relation' => 'thumbnail',
        ]), collect([]));

        $action->handle($fields, collect([$page]));

        $page->refresh();
        $this->assertCount(1, $page->attachments);
        $this->assertSame($fileName, $page->attachments->first()->file_name);

        $chunkManager->cleanupUpload($uploadId);
    }

    public function test_chunked_file_field_fill_for_action_resolves_json_payload(): void
    {
        /** @var ChunkManager $chunkManager */
        $chunkManager = app(ChunkManager::class);
        $uploadId = 'field_action_json_test';
        $fileName = 'game.zip';
        $content = 'PK_ZIP_TEST_DATA';

        $chunk = UploadedFile::fake()->createWithContent('chunk_0', $content);
        $chunkManager->saveChunk($uploadId, 0, $chunk);
        $chunkManager->assembleChunks($uploadId, 1, $fileName);

        $field = ChunkedFile::make('File', 'file');

        $request = new NovaRequest([
            'file' => json_encode([
                'upload_id' => $uploadId,
                'file_name' => $fileName,
                'original_name' => $fileName,
            ]),
        ]);

        $actionFields = new ActionFields(collect([]), collect([]));
        $field->fillForAction($request, $actionFields);

        $this->assertInstanceOf(UploadedFile::class, $actionFields->file);
        $this->assertSame($fileName, $actionFields->file->getClientOriginalName());

        $chunkManager->cleanupUpload($uploadId);
    }

    public function test_chunked_file_field_serialization(): void
    {
        $field = ChunkedFile::make('Game Build', 'file')
            ->chunkSize(4 * 1024 * 1024)
            ->maxFileSize(500 * 1024 * 1024);

        $serialized = $field->jsonSerialize();

        $this->assertSame('chunked-file-field', $serialized['component']);
        $this->assertSame(4 * 1024 * 1024, $serialized['chunkSize']);
        $this->assertSame(500 * 1024 * 1024, $serialized['maxFileSize']);
        $this->assertArrayHasKey('uploadUrl', $serialized);
        $this->assertArrayHasKey('cancelUrl', $serialized);
    }

    public function test_chunked_file_field_fill_for_resource(): void
    {
        Storage::fake('public');

        /** @var ChunkManager $chunkManager */
        $chunkManager = app(ChunkManager::class);
        $uploadId = 'field_resource_json_test';
        $fileName = 'resource_file.png';
        $content = 'PNG_RAW_DATA_SAMPLE';

        $chunk = UploadedFile::fake()->createWithContent('chunk_0', $content);
        $chunkManager->saveChunk($uploadId, 0, $chunk);
        $chunkManager->assembleChunks($uploadId, 1, $fileName);

        $field = ChunkedFile::make('File', 'file_name')->disk('public')->path('test_uploads');

        $request = new NovaRequest([
            'file_name' => json_encode([
                'upload_id' => $uploadId,
                'file_name' => $fileName,
                'original_name' => $fileName,
            ]),
        ]);

        $model = new \stdClass;
        $callback = $field->fill($request, $model);
        if (is_callable($callback)) {
            $callback();
        }

        $this->assertNotNull($model->file_name);
        Storage::disk('public')->assertExists($model->file_name);

        $chunkManager->cleanupUpload($uploadId);
    }
}
