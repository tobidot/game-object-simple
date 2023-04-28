<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LuPublishState
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState query()
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LuPublishState whereName($value)
 * @mixin \Eloquent
 */
class LuPublishState extends Model
{
    use HasFactory;
}
