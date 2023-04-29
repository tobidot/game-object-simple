<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LuPublishState
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @method static Builder|LuPublishState newModelQuery()
 * @method static Builder|LuPublishState newQuery()
 * @method static Builder|LuPublishState query()
 * @method static Builder|LuPublishState whereId($value)
 * @method static Builder|LuPublishState whereLabel($value)
 * @method static Builder|LuPublishState whereName($value)
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class LuPublishState extends Model
{
    use HasFactory;
}
