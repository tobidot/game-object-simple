<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
    use Laravel\Nova\Fields\Select;
    use Laravel\Nova\Fields\Image;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Support\Facades\DB;

class LatestTobidotElements extends Lens
{
    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [];

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        return $request->withOrdering($request->withFilters(
            $query->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('tobidot_elements as te')
                    ->whereRaw('te.major = (SELECT MAX(major) FROM tobidot_elements WHERE name = te.name)')
                    ->whereRaw('te.minor = (SELECT MAX(minor) FROM tobidot_elements WHERE name = te.name AND major = te.major)')
                    ->whereRaw('te.patch = (SELECT MAX(patch) FROM tobidot_elements WHERE name = te.name AND major = te.major AND minor = te.minor)')
                    ->groupBy('name', 'major', 'minor', 'patch');
            })
        ));
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Image::make(__('Icon'), 'icon')
                ->disk('media-library'),
            Text::make(__('Name'), 'name')->sortable(),
            Select::make(__('Kind'), 'kind')->options([
                'element' => __('Element'),
                'library' => __('Library'),
            ])->displayUsingLabels()->sortable(),
            Number::make(__('Major'), 'major')->sortable(),
            Number::make(__('Minor'), 'minor')->sortable(),
            Number::make(__('Patch'), 'patch')->sortable(),
        ];
    }

    /**
     * Get the cards available on the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return parent::actions($request);
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'latest-tobidot-elements';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return __('Latest Versions');
    }
}
