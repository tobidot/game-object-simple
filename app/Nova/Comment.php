<?php

namespace App\Nova;

use Faker\Provider\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Email;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Comment extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Comment>
     */
    public static string $model = \App\Models\Comment::class;

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
        'author',
        'email',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request) : array
    {
        return [
            ID::make()->sortable(),
            Boolean::make("Visible", 'visible')->sortable()->filterable(),
            DateTime::make("Created At", 'created_at')->sortable(),
            \Laravel\Nova\Fields\Text::make("Title", 'title')->filterable(),
            \Laravel\Nova\Fields\Text::make("Author", 'author')->sortable()->filterable(),
            Email::make("Email", 'email')->sortable()->filterable(),
            Trix::make("Content", 'content')
                ->rules(['required', 'string', 'max:65535'])
                ->alwaysShow()
                ->filterable(),
            MorphTo::make("Commentable", 'commentable')->types([
                Page::class,
                Project::class,
            ])->searchable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request) : array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request) : array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request) : array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request) : array
    {
        return [];
    }
}
