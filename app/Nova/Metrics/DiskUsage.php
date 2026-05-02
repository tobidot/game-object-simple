<?php

namespace App\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class DiskUsage extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param NovaRequest $request
     * @return PartitionResult
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $path = storage_path('app/public');

        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return $this->result([
            __('Used (GB)') => round($used / 1024 / 1024 / 1024, 2),
            __('Free (GB)') => round($free / 1024 / 1024 / 1024, 2),
        ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey(): string
    {
        return 'disk-usage';
    }

    public function name()
    {
        return __('Disk Usage (Public)');
    }
}
