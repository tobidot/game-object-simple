<?php

namespace App\Models\Scopes;

use App\Enums\PublishState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope for only visible items
 */
class VisibleScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model) : void
    {
        if (auth()->check()) {
            return;
        }
        $builder->where('publish_state_id', '=', PublishState::PUBLISHED->value);
    }
}
