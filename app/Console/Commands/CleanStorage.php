<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;

/**
 * Removes every stored file the site no longer references. Without `--delete` it only reports.
 *
 * A file stays when any of these holds:
 * - it belongs to an attachment linked to a page or package (`attachables`);
 * - a page's text mentions it, in any format (markdown image, old Trix HTML, thumbnail);
 * - a kept package's code mentions it: `dll-tce-assets` names shared media by URL, with no link row.
 *
 * Only the folders below are touched, so anything else on the disk is left alone.
 */
class CleanStorage extends Command
{
    private const MANAGED_FOLDERS = ['media', 'releases', 'tobidot-elements', 'tce-packages'];

    private const TEXT_FILE_EXTENSIONS = ['js', 'mjs', 'css', 'html', 'json', 'svg', 'txt', 'md', 'xml'];

    private const STALE_CHUNK_SECONDS = 86400;

    protected $signature = 'app:clean-storage {--delete : Delete for real; without it the command only reports}';

    protected $description = 'Remove stored files that no page or package references';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $linked_paths = $this->linkedAttachmentPaths();

        if ($linked_paths === []) {
            $this->error('No attachment is linked to anything. Refusing to treat the whole storage as unreferenced.');

            return self::FAILURE;
        }

        $kept_roots = $this->referencedRoots($disk, $linked_paths);
        $removable = $this->removableFiles($disk, $linked_paths, $kept_roots);
        $orphan_rows = $this->orphanAttachmentRows($linked_paths, $kept_roots);
        $stale_chunks = $this->staleChunkFolders();

        $this->report($disk, $removable, $orphan_rows, $stale_chunks);

        if (! $this->option('delete')) {
            $this->info('Nothing was deleted. Run again with --delete to remove the above.');

            return self::SUCCESS;
        }

        $this->deleteFiles($disk, $removable, $linked_paths);
        $this->deleteEmptyFolders($disk);
        Attachment::query()->whereIn('id', $orphan_rows)->delete();
        foreach ($stale_chunks as $folder) {
            Storage::disk('local')->deleteDirectory($folder);
        }
        $this->info('Deleted.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string> paths of attachments linked to anything, without a leading slash
     */
    private function linkedAttachmentPaths(): array
    {
        return DB::table('attachments')
            ->whereIn('id', DB::table('attachables')->select('attachment_id'))
            ->pluck('path')
            ->map(fn (string $path): string => ltrim($path, '/'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * What the pages' text and the kept packages' code mention, cut to the first two path segments.
     * `media/go-logo/<id>/intro.mp4` keeps all of `media/go-logo`: too much rather than too little.
     *
     * @param  array<int, string>  $linked_paths
     * @return array<string, true>
     */
    private function referencedRoots(Filesystem $disk, array $linked_paths): array
    {
        $roots = [];

        foreach (DB::table('pages')->get(['content', 'content_html_legacy', 'thumbnail']) as $page) {
            $text = $page->content.' '.$page->content_html_legacy.' '.$page->thumbnail;
            $roots += $this->rootsMentionedIn($text);
        }

        foreach ($linked_paths as $path) {
            if (! str_starts_with($path, 'tce-packages/')) {
                continue;
            }
            foreach ($disk->allFiles($path) as $file) {
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::TEXT_FILE_EXTENSIONS, true)) {
                    $roots += $this->rootsMentionedIn((string) $disk->get($file));
                }
            }
        }

        return $roots;
    }

    /**
     * @return array<string, true>
     */
    private function rootsMentionedIn(string $text): array
    {
        $folders = implode('|', self::MANAGED_FOLDERS);
        $roots = [];
        if (preg_match_all('#/('.$folders.')/([A-Za-z0-9._%~+-]+)#', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $roots[$match[1].'/'.urldecode($match[2])] = true;
            }
        }

        return $roots;
    }

    /**
     * @param  array<int, string>  $linked_paths
     * @param  array<string, true>  $kept_roots
     * @return array<int, string>
     */
    private function removableFiles(Filesystem $disk, array $linked_paths, array $kept_roots): array
    {
        $linked = array_fill_keys($linked_paths, true);
        $removable = [];

        foreach (self::MANAGED_FOLDERS as $folder) {
            foreach ($disk->allFiles($folder) as $file) {
                if (dirname($file) === $folder && str_starts_with(basename($file), '.')) {
                    continue;
                }
                if (! $this->isKept($file, $linked, $kept_roots)) {
                    $removable[] = $file;
                }
            }
        }

        return $removable;
    }

    /**
     * @param  array<string, true>  $linked
     * @param  array<string, true>  $kept_roots
     */
    private function isKept(string $file, array $linked, array $kept_roots): bool
    {
        if (isset($kept_roots[$this->root($file)])) {
            return true;
        }

        // Attachments may sit deeper than the root, e.g. `media/go-logo/<id>`.
        for ($path = dirname($file); $path !== '.'; $path = dirname($path)) {
            if (isset($linked[$path])) {
                return true;
            }
        }

        return false;
    }

    private function root(string $path): string
    {
        return implode('/', array_slice(explode('/', ltrim($path, '/')), 0, 2));
    }

    /**
     * @param  array<int, string>  $linked_paths
     * @param  array<string, true>  $kept_roots
     * @return array<int, int>
     */
    private function orphanAttachmentRows(array $linked_paths, array $kept_roots): array
    {
        $linked = array_fill_keys($linked_paths, true);

        return DB::table('attachments')
            ->get(['id', 'path'])
            ->reject(function (object $attachment) use ($linked, $kept_roots): bool {
                $path = ltrim($attachment->path, '/');

                return isset($linked[$path]) || isset($kept_roots[$this->root($path)]);
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Chunked uploads left behind: only their zip is needed, and only while the upload runs.
     *
     * @return array<int, string>
     */
    private function staleChunkFolders(): array
    {
        $disk = Storage::disk('local');
        $limit = now()->getTimestamp() - self::STALE_CHUNK_SECONDS;

        return array_values(array_filter(
            $disk->directories('chunks'),
            function (string $folder) use ($disk, $limit): bool {
                $newest = 0;
                foreach ($disk->allFiles($folder) as $file) {
                    $newest = max($newest, $disk->lastModified($file));
                }

                return $newest < $limit;
            },
        ));
    }

    /**
     * A name with a backslash cannot be measured (see `deleteFiles`); it only changes the report.
     */
    private function sizeOf(Filesystem $disk, string $file): int
    {
        try {
            return $disk->size($file);
        } catch (FilesystemException) {
            return 0;
        }
    }

    /**
     * @param  array<int, string>  $removable
     * @param  array<int, int>  $orphan_rows
     * @param  array<int, string>  $stale_chunks
     */
    private function report(Filesystem $disk, array $removable, array $orphan_rows, array $stale_chunks): void
    {
        $by_root = [];
        foreach ($removable as $file) {
            $root = $this->root($file);
            $by_root[$root] ??= ['files' => 0, 'bytes' => 0];
            $by_root[$root]['files']++;
            $by_root[$root]['bytes'] += $this->sizeOf($disk, $file);
        }
        ksort($by_root);

        $this->table(
            ['Unreferenced', 'Files', 'MB'],
            array_map(
                fn (string $root, array $total): array => [$root, $total['files'], round($total['bytes'] / 1048576, 1)],
                array_keys($by_root),
                $by_root,
            ),
        );

        foreach (self::MANAGED_FOLDERS as $folder) {
            $bytes = 0;
            $files = 0;
            foreach ($by_root as $root => $total) {
                if (str_starts_with($root, $folder.'/')) {
                    $bytes += $total['bytes'];
                    $files += $total['files'];
                }
            }
            $this->line(sprintf('%s: %d files, %.1f MB unreferenced', $folder, $files, $bytes / 1048576));
        }
        $this->line(count($orphan_rows).' attachment rows without a link or a mention');
        $this->line(count($stale_chunks).' unfinished chunked uploads older than a day');
    }

    /**
     * A root with nothing kept goes as a whole folder. Windows zips left names with a backslash, which
     * the disk rewrites to a slash or refuses: asked for by name such a file would stay.
     *
     * @param  array<int, string>  $removable
     * @param  array<int, string>  $linked_paths
     */
    private function deleteFiles(Filesystem $disk, array $removable, array $linked_paths): void
    {
        $roots = array_unique(array_map(fn (string $file): string => $this->root($file), $removable));

        foreach ($roots as $root) {
            if ($this->holdsLinkedAttachment($root, $linked_paths)) {
                $this->deleteLooseFiles($disk, $root, $removable);
            } elseif ($disk->directoryExists($root)) {
                $disk->deleteDirectory($root);
            } else {
                $disk->delete($root);
            }
        }
    }

    /**
     * @param  array<int, string>  $removable
     */
    private function deleteLooseFiles(Filesystem $disk, string $root, array $removable): void
    {
        foreach ($removable as $file) {
            if ($this->root($file) === $root) {
                $disk->delete($file);
            }
        }
    }

    /**
     * @param  array<int, string>  $linked_paths
     */
    private function holdsLinkedAttachment(string $root, array $linked_paths): bool
    {
        foreach ($linked_paths as $path) {
            if (str_starts_with($path, $root.'/')) {
                return true;
            }
        }

        return false;
    }

    private function deleteEmptyFolders(Filesystem $disk): void
    {
        foreach (self::MANAGED_FOLDERS as $folder) {
            $folders = $disk->allDirectories($folder);
            usort($folders, fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'));
            foreach ($folders as $path) {
                if ($disk->allFiles($path) === [] && $disk->allDirectories($path) === []) {
                    $disk->deleteDirectory($path);
                }
            }
        }
    }
}
