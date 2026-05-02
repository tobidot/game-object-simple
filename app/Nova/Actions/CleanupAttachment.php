<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class CleanupAttachment extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            // Detach from TobidotElements
            \Illuminate\Support\Facades\DB::table('tobidot_elements')
                ->where('attachment_id', $model->id)
                ->update(['attachment_id' => null]);

            // Detach from attachables (MorphToMany)
            \Illuminate\Support\Facades\DB::table('attachables')
                ->where('attachment_id', $model->id)
                ->delete();

            // Clear Project thumbnails if they use this attachment's URL
            \Illuminate\Support\Facades\DB::table('projects')
                ->where('thumbnail', $model->url)
                ->update(['thumbnail' => null]);

            // Delete files from disk
            $path = ltrim($model->path, '/');
            if (!empty($path)) {
                Storage::disk('public')->deleteDirectory($path);
            }

            // Delete record
            $model->delete();
        }

        return Action::message('Selected attachments and their files have been deleted.');
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }

    public function name()
    {
        return __('Cleanup Attachment');
    }
}
