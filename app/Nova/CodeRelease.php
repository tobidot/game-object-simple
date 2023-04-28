<?php

namespace App\Nova;

use App\Helpers\NovaHelper;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class CodeRelease extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CodeRelease>
     */
    public static string $model = \App\Models\CodeRelease::class;

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
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request) : array
    {
        return [
            ID::make()->sortable(),
            DateTime::make(__('Created at'), 'created_at')
                ->rules(['required'])
                ->required(),
            Text::make(__('Version'), 'version')
                ->rules(['required','string', 'regex:/\d+\.\d+\.\d+/'])
                ->required(),
            $this->makeRating(__('Completeness'), 'completeness'),
            $this->makeRating(__('Fun'), 'fun'),
            $this->makeRating(__('Complexity'), 'complexity'),
            MorphToMany::make(__('Attachments'), 'attachments', Attachment::class)
                ->fields(new AttachablePivotFields()),

        ];
    }

    public function makeRating(
        string $label,
        string $attribute
    ) : Number {
        return Number::make($label, $attribute)
            ->rules(['required', 'float', 'min:0', 'max:1'])
            ->step('0.01')
            ->min(0)
            ->max(1);
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
