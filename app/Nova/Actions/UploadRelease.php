<?php

namespace App\Nova\Actions;

use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Models\Project;
use App\Services\Models\AttachmentService;
use App\Services\Models\ProjectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use function GuzzleHttp\Promise\inspect;

class UploadRelease extends Action
{
    use InteractsWithQueue, Queueable;

    public $onlyOnDetail = true;

    /**
     * Perform the action on the given models.
     *
     * @param ActionFields $fields
     * @param Collection $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $project = $models->first();
        if (!($project instanceof Project)) {
            return Action::danger("Action only applicable to Projects");
        }
        $version = $fields->version ?? '0.0.1';
        $zip = $fields->zip ?? null;
        if (empty($zip)) {
            return Action::danger("No Zip File Uploaded");
        }
        AppHelper::resolve(ProjectService::class)
            ->release(
                $project, $zip, $version,
                $fields->completeness ?? null,
                $fields->fun ?? null,
                $fields->complexity ?? null,
            );
        return Action::message("Release Uploaded");
    }

    /**
     * Get the fields available on the action.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request): array
    {
        // detect the related resource id
        $resource_id = $request->resourceId ?? $request->viaResource ?? $request->resources;
        $project = Project::query()->find($resource_id);
        $default_version_tag = "0.0.1";
        $latest_release = $project->codeReleases()->latest('created_at')->first();
        if ($latest_release instanceof CodeRelease) {
            $previous_version = $latest_release->version;
            $pattern = "/(\d+\.\d+\.)(\d+)/";
            $next_version = preg_replace_callback(
                $pattern,
                fn(array $matches) => "$matches[1]" . strval($matches[2] + 1),
                $previous_version
            );
            if (is_string($next_version)) {
                $default_version_tag = $next_version;
            }
        }
        return [
            File::make(__('Release Zip'), 'zip')
                ->rules(['required'])
                ->required(),
            Text::make(__('Version'), 'version')
                ->rules(['required', 'regex:/\d+\.\d+\.\d+/'])
                ->default($default_version_tag)
                ->required(),
            Number::make(__("Fun"), 'fun')
                ->min(0)->max(10)->step(1)
                ->rules(['required', 'numeric', 'min:0', 'max:10'])
                ->default($latest_release->fun ?? 0)
                ->required(),
            Number::make(__("Complexity"), 'complexity')
                ->min(0)->max(10)->step(1)
                ->rules(['required', 'numeric', 'min:0', 'max:10'])
                ->default($latest_release->complexity ?? 0)
                ->required(),
            Number::make(__("Completeness"), 'completeness')
                ->min(0)->max(10)->step(1)
                ->rules(['required', 'numeric', 'min:0', 'max:10'])
                ->default($latest_release->completeness ?? 0)
                ->required(),
        ];
    }
}
