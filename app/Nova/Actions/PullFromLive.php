<?php

namespace App\Nova\Actions;

use App\Enums\LiveSyncSection;
use App\Helpers\AppHelper;
use App\Services\LiveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Throwable;

/**
 * Replaces one section of the local data with the live site's (see LiveSyncService). Local
 * instances only: the action is hidden everywhere else and the service refuses to run there.
 */
class PullFromLive extends Action
{
    use InteractsWithQueue, Queueable;

    public $standalone = true;

    public function __construct(private readonly LiveSyncSection $section)
    {
        $this->confirmText = __('This replaces all local :section with the live data, and downloads their files from the live site.', [
            'section' => $section->label(),
        ]);
        $this->confirmButtonText = __('Pull from live');
        $this->canSee(fn () => LiveSyncService::pullAllowed());
    }

    public function name(): string
    {
        return __('Pull :section from live', ['section' => $this->section->label()]);
    }

    public function uriKey(): string
    {
        return "pull-{$this->section->value}-from-live";
    }

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        try {
            $result = AppHelper::resolve(LiveSyncService::class)->pull(
                $this->section,
                (string) $fields->live_url,
                $fields->token ?: config('services.live.token'),
                ! $fields->files_only,
            );
        } catch (Throwable $exception) {
            report($exception);

            return Action::danger(__('Pull failed: :message', ['message' => $exception->getMessage()]));
        }

        $files = $result['files'];
        $message = __(':rows rows replaced, :downloaded files downloaded, :present already in place.', [
            'rows' => array_sum($result['rows']),
            'downloaded' => $files['downloaded'],
            'present' => $files['present'],
        ]);
        if ($files['failed'] !== []) {
            return Action::danger($message.' '.__(':count failed (see the log): :first', [
                'count' => count($files['failed']),
                'first' => $files['failed'][0],
            ]));
        }

        return Action::message($message);
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Text::make(__('Live URL'), 'live_url')
                ->default(config('services.live.url'))
                ->rules(['required', 'url']),
            Password::make(__('Live API token'), 'token')
                // Password hashes what it fills; the token has to reach live as typed.
                ->fillUsing(fn (NovaRequest $request, object $model, string $attribute, string $request_attribute) => $model->{$attribute} = $request->input($request_attribute))
                ->help(__('A Sanctum token of the live admin (Users → Generate API Token on live). Empty: LIVE_API_TOKEN from .env.')),
            Boolean::make(__('Files only'), 'files_only')
                ->help(__('Keep the local rows (e.g. after importing a live DB dump) and only download the files they point to.')),
        ];
    }
}
