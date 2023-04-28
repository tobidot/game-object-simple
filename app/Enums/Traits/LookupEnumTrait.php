<?php

namespace App\Enums\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait LookupEnumTrait
{
    public abstract static function table(): string;

    public static function options() : array {
        return DB::table(self::table())
            ->get(['id', 'name', 'label'])
            ->mapWithKeys(fn($entry)=>[
                $entry->id => $entry->label
            ])
            ->all();
    }
}
