<?php

namespace App\Nova;

use App\Enums\LiveSyncSection;
use App\Helpers\AppHelper;
use App\Services\Models\AttachmentService;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * The v3 package resource — see "Publishing & Discovery: Data Model". The legacy
 * `App\Nova\TobidotElement` resource is kept, read-only in spirit, for porting reference.
 */
class TcePackage extends Resource
{
    /**
     * @var class-string<\App\Models\TcePackage>
     */
    public static string $model = \App\Models\TcePackage::class;

    public static $title = 'name';

    public function subtitle(): string
    {
        /** @var \App\Models\TcePackage $this */
        return "$this->namespace/$this->name @ $this->major.$this->minor.$this->patch";
    }

    /**
     * @var array
     */
    public static $search = [
        'id',
        'namespace',
        'name',
        'description',
    ];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make(__('Namespace'), 'namespace')->required(),
            Text::make(__('Name'), 'name')->required(),
            Textarea::make(__('Description'), 'description')->nullable(),
            Select::make(__('Kind'), 'kind')->options([
                'element' => __('Element'),
                'library' => __('Library'),
                'sdk' => __('SDK (build-time only, vendored by the generator)'),
            ])->displayUsingLabels(),
            Image::make(__('Icon'), function () {
                $iconAttachment = $this->iconAttachment()->first();
                if (! $iconAttachment) {
                    return null;
                }

                return AppHelper::resolve(AttachmentService::class)->getPublicFilePath($iconAttachment);
            }, 'public')
                ->readonly()
                ->showOnIndex()
                ->showOnDetail(),
            Number::make(__('Major'), 'major')->required()->default(0),
            Number::make(__('Minor'), 'minor')->required()->default(1),
            Number::make(__('Patch'), 'patch')->required()->default(0),
            Number::make(__('Width'), 'width')->required()->default(200),
            Number::make(__('Height'), 'height')->required()->default(200),
            Code::make(__('Extra'), 'extra')->nullable()->json(),
            MorphToMany::make(__('Code Attachment'), 'codeAttachment', Attachment::class),
            MorphToMany::make(__('Icon Attachment'), 'iconAttachment', Attachment::class),
            BelongsToMany::make(__('Dependencies'), 'dependencies', \App\Nova\TcePackage::class)
                ->fields(function () {
                    return [
                        Number::make(__('Required Major'), 'required_major'),
                        Number::make(__('Required Minor'), 'required_minor'),
                        Number::make(__('Required Patch'), 'required_patch'),
                    ];
                }),
        ];
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [
            (new Actions\UploadAttachment)->showInline(),
            Action::openInNewTab(
                __('View Fullscreen'),
                fn (\App\Models\TcePackage $model) => route('tce-packages.view', $model)
            )->sole(),
            new Actions\PullFromLive(LiveSyncSection::CODE),
        ];
    }
}
