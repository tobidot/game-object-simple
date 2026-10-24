<?php

namespace App\Services;

use App\Enums\AttachmentType;
use App\Enums\LiveSyncSection;
use App\Models\Attachment;
use App\Services\Models\AttachmentService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Pulls the live site's data into a local instance, one LiveSyncSection at a time, overriding
 * the local state. The live site exports a section's rows (export(), served by
 * LiveSyncController); the local instance replaces its rows with them and downloads every file
 * those rows point to (pull()).
 *
 * Files come from the live site's public storage (`{live}/storage/...` — everything on the public
 * disk is served publicly anyway), are checked against the attachment's sha256 before they
 * replace anything, and zips are unpacked the way an upload is. Rows travel raw (DB::table), so
 * global scopes, casts and model events never touch them; only columns the local schema has are
 * written.
 */
class LiveSyncService
{
    /** Polymorphic tables shared by several sections, with the column that says which section owns a row. */
    private const SHARED_TABLES = [
        'attachables' => 'attachable_type',
        'nova_trix_attachments' => 'attachable_type',
    ];

    private const INSERT_CHUNK = 500;

    private const DOWNLOAD_CHUNK = 1 << 20;

    public function __construct(private readonly AttachmentService $attachment_service) {}

    /**
     * The section's rows, as the live site serves them to a pulling instance.
     *
     * @return array{section: string, tables: array<string, list<array<string, mixed>>>}
     */
    public function export(LiveSyncSection $section): array
    {
        $tables = [];
        foreach ($section->tables() as $table) {
            $tables[$table] = $this->rows(DB::table($table));
        }
        foreach ($this->sharedTables($section) as $table => $column) {
            $tables[$table] = $this->rows(DB::table($table)->whereIn($column, $section->morphTypes()));
        }
        if ($section !== LiveSyncSection::MEDIA) {
            // The section's own attachments travel with it: pulling code alone brings its icons and scripts.
            $attachment_ids = array_values(array_unique(array_column($tables['attachables'], 'attachment_id')));
            $tables['attachments'] = $this->rows(DB::table('attachments')->whereIn('id', $attachment_ids));
        }

        return ['section' => $section->value, 'tables' => $tables];
    }

    /**
     * Replaces the local section with the live one and downloads the files it points to.
     *
     * @param  bool  $with_rows  false keeps the local rows (e.g. after importing a live DB dump) and only downloads their files.
     * @return array{rows: array<string, int>, files: array{downloaded: int, present: int, failed: list<string>}}
     */
    public function pull(LiveSyncSection $section, string $live_url, ?string $token, bool $with_rows = true): array
    {
        $live_url = $this->assertPullTarget($live_url);
        set_time_limit(0);

        $rows = [];
        if ($with_rows) {
            if (! $token) {
                throw new InvalidArgumentException('Pulling rows needs an API token of the live site.');
            }
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(300)
                ->get("$live_url/api/v1/live-sync/$section->value")
                ->throw();
            $rows = $this->replaceRows($section, $this->validatedTables($section, $response->json()));
        }
        $files = $this->downloadFiles($section, $live_url);

        $failed = count($files['failed']);
        Log::info("Pulled $section->value from $live_url: ".json_encode($rows)
            ." rows, {$files['downloaded']} files downloaded, {$files['present']} present, $failed failed.");

        return ['rows' => $rows, 'files' => $files];
    }

    private function assertPullTarget(string $live_url): string
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Pulling from live overrides local data; it never runs in production.');
        }
        $live_url = rtrim(trim($live_url), '/');
        $host = parse_url($live_url, PHP_URL_HOST);
        if (! is_string($host) || ! in_array(parse_url($live_url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException("Not a valid live URL: $live_url");
        }
        if (strcasecmp($host, (string) parse_url((string) config('app.url'), PHP_URL_HOST)) === 0) {
            throw new InvalidArgumentException('The live URL points at this instance itself.');
        }

        return $live_url;
    }

    /** @return array<string, string> */
    private function sharedTables(LiveSyncSection $section): array
    {
        return $section->morphTypes() === [] ? [] : self::SHARED_TABLES;
    }

    /** @return list<array<string, mixed>> */
    private function rows(Builder $query): array
    {
        return $query->get()->map(fn (object $row) => (array) $row)->all();
    }

    /**
     * The export payload, checked to be exactly the section's tables with flat rows; a shared
     * table may only carry rows of the section's own morph types.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function validatedTables(LiveSyncSection $section, mixed $payload): array
    {
        if (! is_array($payload) || ($payload['section'] ?? null) !== $section->value || ! is_array($payload['tables'] ?? null)) {
            throw new InvalidArgumentException("The live site did not answer with the $section->value section.");
        }
        $expected = [...$section->tables(), ...array_keys($this->sharedTables($section))];
        if ($section !== LiveSyncSection::MEDIA) {
            $expected[] = 'attachments';
        }
        $tables = $payload['tables'];
        $unexpected = array_diff(array_keys($tables), $expected);
        $missing = array_diff($expected, array_keys($tables));
        if ($unexpected !== [] || $missing !== []) {
            throw new InvalidArgumentException('Unexpected tables from the live site: '
                .json_encode(['unexpected' => array_values($unexpected), 'missing' => array_values($missing)]));
        }
        foreach ($tables as $table => $rows) {
            if (! array_is_list($rows)) {
                throw new InvalidArgumentException("Table $table from the live site is not a list of rows.");
            }
            foreach ($rows as $row) {
                $flat = is_array($row) && $row !== [] && ! array_is_list($row)
                    && array_filter($row, fn (mixed $value) => $value !== null && ! is_scalar($value)) === [];
                if (! $flat) {
                    throw new InvalidArgumentException("Table $table from the live site has a malformed row.");
                }
            }
        }
        foreach ($this->sharedTables($section) as $table => $column) {
            foreach ($tables[$table] as $row) {
                if (! in_array($row[$column] ?? null, $section->morphTypes(), true)) {
                    throw new InvalidArgumentException("Table $table from the live site has a row of another section.");
                }
            }
        }

        return $tables;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $tables
     * @return array<string, int>
     */
    private function replaceRows(LiveSyncSection $section, array $tables): array
    {
        $counts = [];
        $removed_attachments = [];
        DB::transaction(function () use ($section, $tables, &$counts, &$removed_attachments) {
            // The rows arrive with their live ids, so references between them stay intact.
            Schema::disableForeignKeyConstraints();
            try {
                foreach ($section->tables() as $table) {
                    if ($table === 'attachments') {
                        $removed_attachments = DB::table('attachments')
                            ->whereNotIn('id', array_column($tables['attachments'], 'id'))
                            ->get()
                            ->all();
                    }
                    DB::table($table)->delete();
                    $counts[$table] = $this->insert($table, $tables[$table]);
                }
                foreach ($this->sharedTables($section) as $table => $column) {
                    DB::table($table)->whereIn($column, $section->morphTypes())->delete();
                    $counts[$table] = $this->insert($table, $tables[$table]);
                }
                if ($section !== LiveSyncSection::MEDIA) {
                    $counts['attachments'] = $this->upsertById('attachments', $tables['attachments']);
                }
                // A link to an attachment that no longer exists would point at nothing.
                DB::table('attachables')->whereNotIn('attachment_id', DB::table('attachments')->select('id'))->delete();
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });
        $this->deleteFoldersOf($removed_attachments);

        return $counts;
    }

    /** @param list<array<string, mixed>> $rows */
    private function insert(string $table, array $rows): int
    {
        $rows = $this->onlyLocalColumns($table, $rows);
        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        return count($rows);
    }

    /** @param list<array<string, mixed>> $rows */
    private function upsertById(string $table, array $rows): int
    {
        $rows = $this->onlyLocalColumns($table, $rows);
        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            DB::table($table)->upsert($chunk, ['id']);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function onlyLocalColumns(string $table, array $rows): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_map(fn (array $row) => array_intersect_key($row, $columns), $rows);
    }

    /** @param list<object> $attachments rows of attachments that are gone locally now */
    private function deleteFoldersOf(array $attachments): void
    {
        $disk = Storage::disk('public');
        foreach ($attachments as $attachment) {
            $path = $this->safePath($this->folderOf($attachment));
            // Only an attachment's own uuid folder ("media/<uuid>"), never a whole prefix, and never one still in use.
            if ($path !== null && str_contains($path, '/') && ! DB::table('attachments')->where('path', $attachment->path)->exists()) {
                $disk->deleteDirectory($path);
            }
        }
    }

    /** @return array{downloaded: int, present: int, failed: list<string>} */
    private function downloadFiles(LiveSyncSection $section, string $live_url): array
    {
        $result = ['downloaded' => 0, 'present' => 0, 'failed' => []];
        $count = function (callable $sync, string $label) use (&$result) {
            try {
                $sync() ? $result['downloaded']++ : $result['present']++;
            } catch (Throwable $exception) {
                $result['failed'][] = "$label: {$exception->getMessage()}";
            }
        };

        $attachments = DB::table('attachments')->orderBy('id');
        if ($section !== LiveSyncSection::MEDIA) {
            $attachments->whereIn('id', DB::table('attachables')
                ->whereIn('attachable_type', $section->morphTypes())
                ->select('attachment_id'));
        }
        foreach ($attachments->get() as $attachment) {
            $count(fn () => $this->syncAttachmentFile($attachment, $live_url), "attachment #$attachment->id");
        }
        if ($section->morphTypes() !== []) {
            $trix_attachments = DB::table('nova_trix_attachments')->whereIn('attachable_type', $section->morphTypes())->get();
            foreach ($trix_attachments as $trix_attachment) {
                $count(fn () => $this->syncTrixFile($trix_attachment, $live_url), "editor upload #$trix_attachment->id");
            }
        }

        return $result;
    }

    /** @return bool whether the file was downloaded (false: it was already in place) */
    private function syncAttachmentFile(object $attachment, string $live_url): bool
    {
        if (! $attachment->file_name) {
            return false;
        }
        $folder = $this->folderOf($attachment);
        $path = $this->safePath("$folder/$attachment->file_name")
            ?? throw new InvalidArgumentException("unsafe path '$attachment->path/$attachment->file_name'");
        $disk = Storage::disk('public');
        $hash = $attachment->hash ?: null;
        $is_zip = $attachment->type === AttachmentType::ZIP->value;

        if ($disk->exists($path) && ($hash === null || hash_file('sha256', $disk->path($path)) === $hash)) {
            if (! $is_zip || count($disk->allFiles($folder)) > 1) {
                return false;
            }
        } else {
            $temp = $this->download($this->liveUrl($live_url, "storage/$path"), $hash);
            try {
                if ($is_zip) {
                    // The folder becomes exactly what the zip holds.
                    $disk->deleteDirectory($folder);
                }
                $this->store($disk->path($path), $temp);
            } finally {
                File::delete($temp);
            }
        }
        if ($is_zip) {
            $this->attachment_service->unpackZip((new Attachment)->setRawAttributes((array) $attachment, true));
        }

        return true;
    }

    /** A file uploaded in a Trix editor (Nova `withFiles`), served from the URL it was saved with. */
    private function syncTrixFile(object $trix_attachment, string $live_url): bool
    {
        if (! is_array(config("filesystems.disks.$trix_attachment->disk"))) {
            throw new InvalidArgumentException("unknown disk '$trix_attachment->disk'");
        }
        $path = $this->safePath(ltrim((string) $trix_attachment->attachment, '/'))
            ?? throw new InvalidArgumentException("unsafe path '$trix_attachment->attachment'");
        $disk = Storage::disk($trix_attachment->disk);
        if ($disk->exists($path)) {
            return false;
        }
        $url_path = $this->safePath(ltrim(rawurldecode((string) parse_url((string) $trix_attachment->url, PHP_URL_PATH)), '/'))
            ?? throw new InvalidArgumentException("unsafe URL '$trix_attachment->url'");
        $temp = $this->download($this->liveUrl($live_url, $url_path), null);
        try {
            $this->store($disk->path($path), $temp);
        } finally {
            File::delete($temp);
        }

        return true;
    }

    /** Streams a file into a temp file and checks its sha256; the caller deletes the temp file. */
    private function download(string $url, ?string $sha256): string
    {
        $response = Http::withOptions(['stream' => true])->timeout(600)->get($url)->throw();
        $temp = (string) tempnam(sys_get_temp_dir(), 'live-sync-');
        $out = fopen($temp, 'wb');
        $body = $response->toPsrResponse()->getBody();
        while (! $body->eof()) {
            fwrite($out, $body->read(self::DOWNLOAD_CHUNK));
        }
        fclose($out);
        if ($sha256 !== null && hash_file('sha256', $temp) !== $sha256) {
            File::delete($temp);
            throw new RuntimeException('the file on live does not match its sha256');
        }

        return $temp;
    }

    private function store(string $absolute_path, string $temp): void
    {
        File::ensureDirectoryExists(dirname($absolute_path));
        File::copy($temp, $absolute_path);
    }

    /** Uploads store their folder with a leading slash ("/media/<uuid>"); on the disk it is relative. */
    private function folderOf(object $attachment): string
    {
        return ltrim((string) $attachment->path, '/');
    }

    private function liveUrl(string $live_url, string $path): string
    {
        return $live_url.'/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /** A relative path without "." or ".." segments, or null — rows from live never leave their disk. */
    private function safePath(string $path): ?string
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }
}
