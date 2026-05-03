<?php

namespace App\Nova;

use App\Helpers\AppHelper;
use App\Services\Models\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\MorphToMany;
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
        'description',
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
            Text::make(__('Name'), 'name')->required(),
            Textarea::make(__('Description'), 'description')->nullable(),
            Select::make(__('Kind'), 'kind')->options([
                'element' => __('Element'),
                'library' => __('Library'),
            ])->displayUsingLabels(),
            Image::make(__('Icon'), function () {
                $iconAttachment = $this->iconAttachment()->first();
                if (!$iconAttachment) {
                    return null;
                }
                return $iconAttachment->path;
            }, 'public')
                ->readonly()
                ->showOnIndex()
                ->showOnDetail(),
            Number::make(__('Major'), 'major')
                ->required()->default(1),
            Number::make(__('Minor'), 'minor')
                ->required()->default(0),
            Number::make(__('Patch'), 'patch')
                ->required()->default(0),
            Boolean::make(__('Standalone'), 'standalone')
                ->required()->default(true),
            Number::make(__('Width'), 'width')
                ->required()->default(200),
            Number::make(__('Height'), 'height')
                ->required()->default(200),
            Code::make(__('Extra'), 'Extra')
                ->nullable()->json(),
            MorphToMany::make(__('Code Attachment'), 'codeAttachment', Attachment::class),
            MorphToMany::make(__('Icon Attachment'), 'iconAttachment', Attachment::class),
            BelongsToMany::make(__('Dependencies'), 'dependencies', TobidotElement::class)
                ->fields(function () {
                    return [
                        Number::make(__('Required Major'), 'required_major'),
                        Number::make(__('Required Minor'), 'required_minor'),
                        Number::make(__('Required Patch'), 'required_patch'),
                    ];
                }),
        ];
    }

    public function store_file(
        Request                    $request,
        \App\Models\TobidotElement $model,
        string                     $attribute,
        string                     $requestAttribute
    ): void
    {
        $file = $request->file($requestAttribute);

        if (!$file) {
            return;
        }
        if ($file->getClientOriginalExtension() === 'zip') {
            $attachment = AppHelper::resolve(AttachmentService::class)->createFromUploadedZipFile(
                $file,
                'tobidot-elements',
                'tobidot-elements',
            );
        } else {
            $attachment = AppHelper::resolve(AttachmentService::class)->createFromUploadedSingleFile(
                $file,
                'tobidot-elements',
                'tobidot-elements',
            );
        }
        $model->save(); // ensure it has an ID
        $model->attachments()->attach($attachment->id, ['relation' => 'code']);
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
        return [
            new Filters\TobidotElementKindFilter(),
            new Filters\StandaloneFilter(),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function lenses(NovaRequest $request): array
    {
        return [
            new Lenses\LatestTobidotElements,
        ];
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
            new Actions\UploadAttachment(),
        ];
    }
}
