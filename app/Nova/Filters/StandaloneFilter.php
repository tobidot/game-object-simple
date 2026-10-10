<?php

namespace App\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class StandaloneFilter extends BooleanFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value['standalone'] && ! $value['not_standalone']) {
            return $query->where('standalone', true);
        }

        if (! $value['standalone'] && $value['not_standalone']) {
            return $query->where('standalone', false);
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            __('Standalone') => 'standalone',
            __('Not Standalone') => 'not_standalone',
        ];
    }
}
