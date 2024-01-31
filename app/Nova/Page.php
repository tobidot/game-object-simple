<?php

namespace App\Nova;

use App\Enums\PublishState;
use App\Helpers\NovaHelper;
use App\Nova\Metrics\ViewsPerDay;
use Illuminate\Http\Request;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
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
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title'
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
            Image::make(__('Thumbnail'), 'thumbnail')
                ->rules(['nullable'])
                ->nullable(),
            NovaHelper::makeEnum('Publish State', 'publish_state_id', PublishState::class)
                ->rules(['required'])
                ->required(),
//            LookupEnum::make(__('Publish State Enum'), 'publish_state_id')
//                ->table('lu_publish_states')
//                ->displayUsingLabels(),
            Text::make(__('Uri'), 'uri')
                ->rules(['required', 'string', 'regex:/[a-z0-9\/]+/'])
                ->required()
                ->help('the uri part to find this page at')
                ->onlyOnForms(),
            Text::make(__('Uri'), function () {
                $page = $this->resource;
                if (!($page instanceof \App\Models\Page)) return '-';
                $url = route('pages.show', ['page' => $page]);
                return "<a class='link-default' href='$url' target='_blank'>Open Page: $url</a>";
            })
                ->exceptOnForms()
                ->asHtml(),
            Text::make(__('View Count'), function () {
                $resource = $this->resource;
                if (!($resource instanceof self::$model)) return '-';
                return $resource->views()->count();
            })->exceptOnForms(),
            Trix::make(__('Content'), 'content')
                ->rules(['required', 'string', 'max:65535'])
                ->required()
                ->withFiles('media-library')
                ->alwaysShow()
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $value = $request->input($attribute);
                    if (is_string($attribute)) {
                        $value = str_replace(['<h1>', '</h1>'], ['<h2>', '</h2>'], $value);
                    }
                    $model->{$attribute} = $value;
                })
                ->resolveUsing(function () {
                    $value = $this->content ?? '';
                    if (!is_string($value)) return $value;
                    return str_replace(['<h2>', '</h2>'], ['<h1>', '</h1>'], $value);
                })
            ,
            MorphMany::make(__('Log Events'), 'logEvents', LogEvent::class),
            BelongsToMany::make(__('Pages'), 'pages', Page::class),
            BelongsToMany::make(__('Projects'), 'projects', Project::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param NovaRequest $request
     * @return array
     * @throws HelperNotSupported
     */
    public function cards(NovaRequest $request): array
    {
        return [
            (new ViewsPerDay(self::$model)),
            (new ViewsPerDay(self::$model))->onlyOnDetail(),
        ];
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
