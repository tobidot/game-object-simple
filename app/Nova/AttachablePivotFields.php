<?php

namespace App\Nova;

use Laravel\Nova\Fields\Text;

class AttachablePivotFields
{
    public function __invoke($request, $relatedModel) : array {
        return [
            Text::make(__('Relation'), 'relation'),
        ];
    }
}
