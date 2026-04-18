<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\LogEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedAttachmentFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-orphaned-files {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean the old folders and files for tobidot-elements without attached models from the disk.';

    protected bool $dry_run = false;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->dry_run = $this->option('dry-run');

        $disk = Storage::disk('public');
        $directories = $disk->allDirectories();

        $count = 0;
        foreach ($directories as $directory) {
            // Check if there is a corresponding model in the database
            if (!Attachment::where('attachments.path', $directory)->exists()) {
                $this->handleOrphanedDirectory($directory);
                $count++;
            }
        }
        if ($this->dry_run) {
            $this->info("Would have deleted $count folders");
        } else {
            $this->info("Deleted $count folders");
        }
        return self::SUCCESS;
    }

    public function handleOrphanedDirectory(
        string $directory
    ): void {
        if ($this->dry_run) {
            $this->info("Would have deleted $directory");
            return ;
        }
        $disk = Storage::disk('public');
        $disk->deleteDirectory($directory);
    }
}
