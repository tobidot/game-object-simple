<?php

namespace App\Nova\Metrics;

use FilesystemIterator;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * What the site itself stores, measured file by file. `DiskUsage` shows the whole volume instead,
 * which moves with whatever else lives on it.
 */
class StorageUsage extends Partition
{
    public function calculate(NovaRequest $request): PartitionResult
    {
        $folders = [
            __('Packages (MB)') => storage_path('app/public/tce-packages'),
            __('Media (MB)') => storage_path('app/public/media'),
            __('Logs (MB)') => storage_path('logs'),
            __('Unfinished uploads (MB)') => storage_path('app/chunks'),
        ];
        $total = $this->bytesIn(storage_path());

        $result = [];
        foreach ($folders as $label => $folder) {
            $bytes = $this->bytesIn($folder);
            $total -= $bytes;
            $result[$label] = round($bytes / 1048576, 1);
        }
        $result[__('Everything else (MB)')] = round($total / 1048576, 1);

        return $this->result($result);
    }

    /**
     * Walks the folder directly: the disk classes cannot see names with a backslash.
     */
    private function bytesIn(string $folder): int
    {
        if (! is_dir($folder)) {
            return 0;
        }

        $bytes = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );
        foreach ($files as $file) {
            if (! $file->isLink() && $file->isFile()) {
                $bytes += $file->getSize();
            }
        }

        return $bytes;
    }

    public function cacheFor()
    {
        return now()->addMinutes(5);
    }

    public function uriKey(): string
    {
        return 'storage-usage';
    }

    public function name()
    {
        return __('Site Storage');
    }
}
