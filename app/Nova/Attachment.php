<?php

namespace App\Nova;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tobidot\LookupEnum\LookupEnum;

class Attachment extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Attachment>
     */
    public static string $model = \App\Models\Attachment::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            DateTime::make(__('Created at'), 'created_at')
                ->rules(['required'])
                ->required(),
            Text::make(__('Url'), 'url')
                ->rules(['required'])
                ->required(),
            Text::make(__('Path'), 'path')
                ->rules(['required'])
                ->required(),
            LookupEnum::make(__('Publish State'), 'publish_state_id')
                ->table(PublishState::table())
                ->rules(['required'])
                ->required(),
            LookupEnum::make(__('Attachment Type'), 'type_id')
                ->table(AttachmentType::table())
                ->rules(['required'])
                ->required(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
