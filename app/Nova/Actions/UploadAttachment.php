<?php

namespace App\Nova\Actions;

use App\Helpers\AppHelper;
use App\Models\Page;
use App\Models\TobidotElement;
use App\Services\Models\AttachmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class UploadAttachment extends Action
{
    use InteractsWithQueue, Queueable;

    public $standalone = true;

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $file = $fields->file;
        if (! $file) {
            return Action::danger(__('No file uploaded.'));
        }

        $prefix = $fields->prefix ?? 'media';
        $attachmentService = AppHelper::resolve(AttachmentService::class);
        $attachment = $attachmentService->createFromUploadedSingleFile($file, $prefix);

        if ($models->isNotEmpty()) {
            foreach ($models as $model) {
                if ($model instanceof TobidotElement) {
                    $relation = $fields->relation ?? 'icon';
                    $model->attachments()->attach($attachment->id, ['relation' => $relation]);
                } elseif ($model instanceof Page) {
                    $relation = $fields->relation ?? 'thumbnail';
                    $model->attachments()->attach($attachment->id, ['relation' => $relation]);
                }
            }
        }

        return Action::message(__('Attachment uploaded successfully.'));
    }

    /**
     * Get the fields available on the action.
     */
    public function fields(NovaRequest $request): array
    {
        $fields = [
            File::make(__('File'), 'file')
                ->rules(['required'])
                ->required(),
            Select::make(__('Prefix'), 'prefix')
                ->options([
                    'media' => 'Media',
                    'tobidot-elements' => 'Tobidot Elements',
                ])
                ->default('media')
                ->rules(['required'])
                ->required(),
        ];

        if ($request->resource() === \App\Nova\TobidotElement::class || $request->resource() === TobidotElement::class) {
            $fields[] = Select::make(__('Relation'), 'relation')
                ->options([
                    'icon' => 'Icon',
                    'code' => 'Code',
                ])
                ->default('icon')
                ->rules(['required'])
                ->required();
        } elseif ($request->resource() === \App\Nova\Page::class || $request->resource() === Page::class) {
            $fields[] = Select::make(__('Relation'), 'relation')
                ->options([
                    'thumbnail' => 'Thumbnail',
                    'attachment' => 'Attachment',
                ])
                ->default('thumbnail')
                ->rules(['required'])
                ->required();
        }

        return $fields;
    }
}
