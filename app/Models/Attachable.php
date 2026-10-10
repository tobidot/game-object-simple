<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * App\Models\Attachable
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Attachable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachable query()
 *
 * @mixin \Eloquent
 */
class Attachable extends MorphPivot {}
