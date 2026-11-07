<?php

namespace App\Nova\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * One row per package that has something to clean up: its newest version.
 */
class TcePackagesWithOlderVersions extends BooleanFilter
{
    private const SAME_PACKAGE = 'other.namespace = tce_packages.namespace AND other.name = tce_packages.name';

    private const VERSION = '(tce_packages.major, tce_packages.minor, tce_packages.patch)';

    private const OTHER_VERSION = '(other.major, other.minor, other.patch)';

    public function name(): string
    {
        return __('Versions');
    }

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        if (empty($value['has_older'])) {
            return $query;
        }

        return $query
            ->whereNotExists(fn (QueryBuilder $other) => $this->otherVersion($other, '>'))
            ->whereExists(fn (QueryBuilder $other) => $this->otherVersion($other, '<'));
    }

    private function otherVersion(QueryBuilder $other, string $direction): void
    {
        $other->selectRaw('1')
            ->from('tce_packages as other')
            ->whereRaw(self::SAME_PACKAGE)
            ->whereRaw(self::OTHER_VERSION." $direction ".self::VERSION);
    }

    public function options(NovaRequest $request): array
    {
        return [
            __('Only packages with older versions') => 'has_older',
        ];
    }
}
