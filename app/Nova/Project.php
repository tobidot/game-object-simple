<?php

namespace App\Nova;

use App\Enums\ProjectState;
use App\Enums\PublishState;
use App\Helpers\NovaHelper;
use App\Nova\Actions\UploadRelease;
use App\Nova\Metrics\ViewsPerDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tobidot\LookupEnum\LookupEnum;

class Project extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Project>
     */
    public static string $model = \App\Models\Project::class;

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
     * @throws HelperNotSupported
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make(__('Title'), 'title')
                ->rules(['required'])
                ->required(),
            Image::make(__('Thumbnail'), 'thumbnail')
                ->rules(['nullable'])
                ->nullable(),
            NovaHelper::makeEnum('Publish State', 'publish_state_id', PublishState::class)
                ->rules(['required'])
                ->required(),
            NovaHelper::makeEnum('Project State', 'state_id', ProjectState::class)
                ->rules(['required'])
                ->required(),
//            LookupEnum::make(__('Publish State'), 'publish_state_id')
//                ->table(PublishState::table())
//                ->displayUsingLabels(),
//            LookupEnum::make(__('Project State'), 'state_id')
//                ->table(ProjectState::table())
//                ->displayUsingLabels(),
            Text::make(__('Uri'), function () {
                $project = $this->resource;
                if (!($project instanceof \App\Models\Project)) return '-';
                $url = route('projects.show', ['project' => $project]);
                return "<a class='link-default' href='$url' target='_blank'>Open Page: $url</a>";
            })
                ->exceptOnForms()
                ->asHtml(),
            Text::make(__('View Count'), function () {
                $resource = $this->resource;
                if (!($resource instanceof self::$model)) return '-';
                return  $resource->views()->count();
            })->exceptOnForms(),
            Trix::make(__('Description'), 'description')
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
                    $value = $this->description ?? '';
                    if (!is_string($value)) return $value;
                    return str_replace(['<h2>', '</h2>'], ['<h1>', '</h1>'], $value);
                }),
            Boolean::make(__('Allow Comments'), 'allow_comments'),
            HasMany::make(__('Releases'), 'codeReleases', CodeRelease::class),
            MorphMany::make(__('Comments'), 'comments', Comment::class),
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
        return [
            new UploadRelease(),
        ];
    }
}
