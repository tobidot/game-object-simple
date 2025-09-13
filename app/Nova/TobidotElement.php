<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TobidotElement extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TobidotElement>
     */
    public static string $model = \App\Models\TobidotElement::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public function subtitle(): string
    {
        /** @var  \App\Models\TobidotElement $this */
        return "$this->major.$this->minor.$this->patch";
    }

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
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request) : array
    {
        return [
            ID::make()->sortable(),
            Text::make(__('Name'), 'name')->required(),
            Textarea::make(__('Description'), 'description')->nullable(),
            Select::make(__('Kind'), 'kind')->options([
                'element' => __('Element'),
                'library' => __('Library'),
            ])->displayUsingLabels(),
            Image::make(__('Icon'), 'icon')
                ->disk('media-library')
                ->nullable(),
            Number::make(__('Major'), 'major')
                ->required()->default(1),
            Number::make(__('Minor'), 'minor')
                ->required()->default(0),
            Number::make(__('Patch'), 'patch')
                ->required()->default(0),
            File::make(__('Content'), 'content')
                ->acceptedTypes('.zip,.js')
                ->path('/'.Str::uuid() )
                ->storeAs(function (Request $request) {
                    $name = Str::snake($request->post('name'),'-');
                    $major = $request->post('major');
                    $patch = $request->post('patch');
                    $minor = $request->post('minor');
                    $version = "$major-$minor-$patch";
                    $extension = $request->file('content')->extension();
                    return "{$version}_$name.$extension";
                })
                ->disk('tobidot-elements')
                ->nullable(),
            URL::make(__('URL'), function() {
                return "/storage/tobidot-elements/$this->content";
            })->readonly()
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
