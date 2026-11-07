<?php

namespace App\Nova\Actions;

use App\Helpers\AppHelper;
use App\Services\StorageCleaner;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use RuntimeException;

class DeleteUnusedStorage extends Action
{
    public $standalone = true;

    public $confirmText = 'Permanently deletes every stored file that no page or package references. An upload not yet used on a page counts as unreferenced. This cannot be undone.';

    public $confirmButtonText = 'Delete';

    public function name(): string
    {
        return __('Delete Unused Storage');
    }

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $cleaner = AppHelper::resolve(StorageCleaner::class);

        try {
            $plan = $cleaner->plan();
        } catch (RuntimeException $exception) {
            return Action::danger($exception->getMessage());
        }
        $cleaner->execute($plan);

        return Action::message(__('Deleted :summary', ['summary' => $plan->summary()]));
    }
}
