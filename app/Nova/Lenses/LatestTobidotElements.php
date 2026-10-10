<?php

namespace App\Nova\Lenses;

use App\Helpers\AppHelper;
use App\Models\TobidotElement as TobidotElementModel;
use App\Nova\Filters\StandaloneFilter;
use App\Nova\Filters\TobidotElementKindFilter;
use App\Services\Models\AttachmentService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;

class LatestTobidotElements extends Lens
{
    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
    ];

    /**
     * Get the query builder / paginator for the lens.
     */
    public static function query(LensRequest $request, $query): Builder
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
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Image::make(__('Icon'), function () {
                /** @var TobidotElementModel $this */
                $iconAttachment = $this->iconAttachment()->first();
                if (! $iconAttachment) {
                    return null;
                }

                return AppHelper::resolve(AttachmentService::class)->getUrl($iconAttachment);
            }),
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
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the lens.
     */
    public function filters(NovaRequest $request): array
    {
        return [
            new TobidotElementKindFilter,
            new StandaloneFilter,
        ];
    }

    /**
     * Get the actions available on the lens.
     */
    public function actions(NovaRequest $request): array
    {
        return parent::actions($request);
    }

    /**
     * Get the URI key for the lens.
     */
    public function uriKey(): string
    {
        return 'latest-tobidot-elements';
    }

    /**
     * Get the displayable name of the lens.
     */
    public function name(): string
    {
        return __('Latest Versions');
    }
}
