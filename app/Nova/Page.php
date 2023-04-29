<?php

namespace App\Nova;

use App\Enums\PublishState;
use App\Helpers\NovaHelper;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tobidot\LookupEnum\LookupEnum;

class Page extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Page>
     */
    public static string $model = \App\Models\Page::class;

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
     * @throws \Exception
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make(__('Title'), 'title')
                ->rules(['required', 'string', 'max:255'])
                ->required()
                ->asHtml(),
            //NovaHelper::makeEnum(__('Publish State'), 'publish_state_id', PublishState::class),
            LookupEnum::make(__('Publish State Enum'), 'publish_state_id')
                ->table('lu_publish_states')
                ->displayUsingLabels(),
            Text::make(__('Uri'), 'uri')
                ->rules(['required', 'string', 'regex:/[a-z0-9\/]+/'])
                ->required()
                ->help('the uri part to find this page at')
                ->onlyOnForms(),
            Text::make(__('Uri'), function() {
                    $page = $this->resource;
                    if (!($page instanceof  \App\Models\Page)) return '-';
                    $url = route('page', ['page' => $page]);
                    return "<a class='link-default' href='$url' target='_blank'>Open Page: $url</a>";
                })
                ->exceptOnForms()
                ->asHtml(),
            Trix::make(__('Content'), 'content')
                ->rules(['required', 'string', 'max:65535'])
                ->required()
                ->withFiles('media-library')
                ->alwaysShow(),
            MorphMany::make(__('Log Events'), 'logEvents', LogEvent::class),
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
