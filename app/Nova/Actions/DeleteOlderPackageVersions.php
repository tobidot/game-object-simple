<?php

namespace App\Nova\Actions;

use App\Helpers\AppHelper;
use App\Services\Models\TcePackageService;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Select any version of a package: its older versions go, the newest stay.
 */
class DeleteOlderPackageVersions extends Action
{
    public $confirmText = 'Permanently removes the older versions of the selected packages. Other packages depending on them move to the newest version. Their files stay until you run "Delete Unused Storage".';

    public $confirmButtonText = 'Delete older versions';

    public function name(): string
    {
        return __('Delete Older Versions');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Number::make(__('Keep newest'), 'keep')
                ->default(1)
                ->min(1)
                ->rules('required', 'integer', 'min:1')
                ->help(__('How many versions of each package stay, counted from the newest.')),
        ];
    }

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $service = AppHelper::resolve(TcePackageService::class);
        $deleted = 0;
        $still_needed = [];

        foreach ($models->unique(fn ($package): string => "$package->namespace/$package->name") as $package) {
            $result = $service->deleteOlderVersions($package, (int) $fields->keep);
            $deleted += $result['deleted'];
            $still_needed = [...$still_needed, ...$result['still_needed']];
        }

        $message = __(':count older versions deleted. Run "Delete Unused Storage" to free their files.', ['count' => $deleted]);
        if ($still_needed !== []) {
            $message .= ' '.__('Kept because a page or a package pins them: :versions', ['versions' => implode(', ', $still_needed)]);
        }

        return Action::message($message);
    }
}
