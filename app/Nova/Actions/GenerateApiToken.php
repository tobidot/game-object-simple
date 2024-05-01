<?php

namespace App\Nova\Actions;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class GenerateApiToken extends Action
{
    use InteractsWithQueue, Queueable;

    public $sole = true;
    public $showInline = true;

    /**
     * Perform the action on the given models.
     *
     * @param ActionFields $fields
     * @param Collection $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $user = $models->first();
        if (!($user instanceof User) ) {
            return Action::danger('Invalid user model');
        }
        // disable all existing tokens
        $user->tokens()->delete();
        // and create a new one
        $token = $user->createToken('API Token');
        return Action::modal('api-token-copier', [
            'message' => 'The API token was generated!',
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Get the fields available on the action.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request) : array
    {
        return [];
    }
}
