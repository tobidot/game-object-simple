<?php

namespace App\Nova\Lenses;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Helpers\NovaHelper;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;

class UnusedAttachments extends Lens
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
            $query->whereDoesntHave('codeReleases')
                ->whereNotExists(function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tobidot_elements')
                        ->whereColumn('tobidot_elements.attachment_id', 'attachments.id');
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
            ID::make()->sortable(),
            DateTime::make(__('Created at'), 'created_at')->sortable(),
            Text::make(__('Url'), 'url'),
            Text::make(__('Path'), 'path'),
            NovaHelper::makeEnum('Publish State', 'publish_state', PublishState::class),
            NovaHelper::makeEnum('Attachment Type', 'type', AttachmentType::class),
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
        return 'unused-attachments';
    }

    public function name()
    {
        return __('Unused Attachments');
    }
}
