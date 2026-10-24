<?php

namespace App\Console\Commands;

use App\Enums\LiveSyncSection;
use App\Services\LiveSyncService;
use Illuminate\Console\Command;

/**
 * The console twin of the Nova action PullFromLive — for large media pulls that would outlast a
 * web request.
 */
class PullFromLiveCommand extends Command
{
    protected $signature = 'live:pull
                            {sections* : posts, media and/or code}
                            {--files-only : Keep the local rows and only download the files they point to}
                            {--url= : The live site; defaults to LIVE_URL}
                            {--token= : A Sanctum token of the live admin; defaults to LIVE_API_TOKEN}';

    protected $description = 'Replace local blog posts, media and/or code with the live data';

    public function handle(LiveSyncService $service): int
    {
        $sections = [];
        foreach ($this->argument('sections') as $name) {
            $section = LiveSyncSection::tryFrom($name);
            if ($section === null) {
                $this->error("Unknown section '$name' (posts, media, code).");

                return self::INVALID;
            }
            $sections[] = $section;
        }
        $url = $this->option('url') ?: (string) config('services.live.url');
        $token = $this->option('token') ?: config('services.live.token');
        $names = implode(', ', array_map(fn (LiveSyncSection $section) => $section->label(), $sections));
        if (! $this->confirm("Replace all local $names with the data from $url?", true)) {
            return self::FAILURE;
        }

        $status = self::SUCCESS;
        foreach ($sections as $section) {
            $result = $service->pull($section, $url, $token, ! $this->option('files-only'));
            $files = $result['files'];
            $this->info("$section->value: ".array_sum($result['rows'])." rows replaced, {$files['downloaded']} files downloaded, {$files['present']} already in place.");
            foreach ($files['failed'] as $failure) {
                $this->warn("  failed: $failure");
                $status = self::FAILURE;
            }
        }

        return $status;
    }
}
