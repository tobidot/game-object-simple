<?php

namespace App\DTO;

/**
 * What `StorageCleaner::plan()` found: nothing has been deleted yet.
 */
final readonly class StorageCleanupPlan
{
    /**
     * @param  array<int, string>  $removable_files  paths on the public disk
     * @param  array<string, array{files: int, bytes: int}>  $by_root  the same files, per folder
     * @param  array<int, int>  $orphan_attachment_ids
     * @param  array<int, string>  $stale_chunk_folders  paths on the local disk
     * @param  array<int, string>  $linked_paths  attachment folders something links to
     */
    public function __construct(
        public array $removable_files,
        public array $by_root,
        public array $orphan_attachment_ids,
        public array $stale_chunk_folders,
        public array $linked_paths,
    ) {}

    public function fileCount(): int
    {
        return count($this->removable_files);
    }

    public function megabytes(): float
    {
        return round(array_sum(array_column($this->by_root, 'bytes')) / 1048576, 1);
    }

    /**
     * @return array<string, array{files: int, megabytes: float}> per top-level storage folder
     */
    public function totalsByFolder(): array
    {
        $totals = [];
        foreach ($this->by_root as $root => $total) {
            $folder = explode('/', $root)[0];
            $totals[$folder] ??= ['files' => 0, 'bytes' => 0];
            $totals[$folder]['files'] += $total['files'];
            $totals[$folder]['bytes'] += $total['bytes'];
        }

        return array_map(
            fn (array $total): array => ['files' => $total['files'], 'megabytes' => round($total['bytes'] / 1048576, 1)],
            $totals,
        );
    }

    public function summary(): string
    {
        $parts = [];
        foreach ($this->totalsByFolder() as $folder => $total) {
            $parts[] = "{$folder}: {$total['files']} files, {$total['megabytes']} MB";
        }
        if ($parts === []) {
            $parts[] = 'no unreferenced files';
        }
        $parts[] = count($this->orphan_attachment_ids).' attachment rows';
        $parts[] = count($this->stale_chunk_folders).' old chunked uploads';

        return implode('; ', $parts);
    }
}
