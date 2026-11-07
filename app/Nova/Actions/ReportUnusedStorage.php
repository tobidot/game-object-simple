<?php

namespace App\Nova\Actions;

use App\Helpers\AppHelper;
use App\Services\StorageCleaner;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use RuntimeException;

class ReportUnusedStorage extends Action
{
    public $standalone = true;

    public $withoutActionEvents = true;

    public $confirmText = 'Lists what "Delete Unused Storage" would remove. Nothing is deleted.';

    public $confirmButtonText = 'Report';

    public function name(): string
    {
        return __('Report Unused Storage');
    }

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        try {
            return Action::message(AppHelper::resolve(StorageCleaner::class)->plan()->summary());
        } catch (RuntimeException $exception) {
            return Action::danger($exception->getMessage());
        }
    }
}
