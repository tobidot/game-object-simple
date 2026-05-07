<?php

namespace App\Nova;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Helpers\AppHelper;
use App\Helpers\NovaHelper;
use App\Nova\CodeRelease;
use App\Nova\TobidotElement;
use App\Services\Models\AttachmentService;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tobidot\LookupEnum\LookupEnum;

/**
 * @mixin \App\Models\Attachment
 */
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
    public static $title = 'file_name';

    public function subtitle(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'path',
        'file_name',
        'hash',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Image::make(__('Preview'),
                function(\App\Models\Attachment $value) {
                    return AppHelper::resolve(AttachmentService::class)->getPublicFilePath($value);
                })
                ->readonly()
                ->exceptOnForms()
                ->canSee(fn() => $this->type === AttachmentType::IMAGE),
            DateTime::make(__('Created at'), 'created_at')
                ->readonly()
                ->exceptOnForms(),
            URL::make(
                __('Url'),
                function(\App\Models\Attachment $value) {
                    return AppHelper::resolve(AttachmentService::class)->getUrl($value);
                }
            )->readonly(),
            Text::make(__('Path'), 'path')
                ->hideFromIndex()
                ->readonly(),
            Text::make(__('File Name'), 'file_name')
                ->hideFromIndex()
                ->readonly(),
            Text::make(__('Hash'), 'hash')
                ->hideFromIndex()
                ->readonly()
                ->hideFromIndex(),
            Text::make(__('Content Type'), 'content_type')
                ->readonly(),
            NovaHelper::makeEnum('Publish State', 'publish_state', PublishState::class)
                ->rules(['required'])
                ->required(),
            NovaHelper::makeEnum('Attachment Type', 'type', AttachmentType::class)
                ->rules(['required'])
                ->required(),

            MorphToMany::make(__('Tobidot Elements'), 'tobidotElements', TobidotElement::class),
            MorphToMany::make(__('Code Releases'), 'codeReleases', CodeRelease::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request): array
    {
        return [
            new Metrics\DiskUsage(),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request): array
    {
        return [
            new Filters\EnumFilter(__('Publish State'), 'publish_state', PublishState::class),
            new Filters\EnumFilter(__('Attachment Type'), 'type', AttachmentType::class),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request): array
    {
        return [
            new Lenses\UnusedAttachments(),
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request): array
    {
        return [
            new Actions\CleanupAttachment(),
            new Actions\UploadAttachment(),
        ];
    }
}
