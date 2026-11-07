<?php

namespace Tests\Feature;

use App\Nova\Metrics\StorageUsage;
use Illuminate\Support\Facades\File;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class StorageUsageMetricTest extends TestCase
{
    private string $real_storage_path;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->real_storage_path = storage_path();
        $this->storage = sys_get_temp_dir().'/storage-usage-'.uniqid();
        File::makeDirectory($this->storage, 0777, true);
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->real_storage_path);
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    private function file(string $path, int $megabytes): void
    {
        $full = $this->storage.'/'.$path;
        File::ensureDirectoryExists(dirname($full));
        file_put_contents($full, str_repeat('x', $megabytes * 1048576));
    }

    public function test_it_measures_the_folders_and_counts_the_rest(): void
    {
        $this->file('app/public/tce-packages/a/index.js', 3);
        $this->file('app/public/media/a/shot.png', 2);
        $this->file('logs/laravel.log', 1);
        $this->file('app/chunks/upl/0', 1);
        $this->file('app/public/releases/old.zip', 4);
        $this->file('app/public/media/a/dist\\app.js', 1);

        $value = (new StorageUsage)->calculate(NovaRequest::create('/'))->value;

        $this->assertEquals(3.0, $value['Packages (MB)']);
        $this->assertEquals(3.0, $value['Media (MB)']);
        $this->assertEquals(1.0, $value['Logs (MB)']);
        $this->assertEquals(1.0, $value['Unfinished uploads (MB)']);
        $this->assertEquals(4.0, $value['Everything else (MB)']);
    }

    public function test_missing_folders_count_as_empty(): void
    {
        $value = (new StorageUsage)->calculate(NovaRequest::create('/'))->value;

        $this->assertEquals(0.0, $value['Packages (MB)']);
    }
}
