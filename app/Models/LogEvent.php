<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\LogEvent
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $type_id
 * @property int $loggable_id
 * @property string $loggable_type
 * @property-read Model|\Eloquent $loggable
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereLoggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereLoggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LogEvent extends Model
{
    use HasFactory;

    public function loggable() : MorphTo {
        return $this->morphTo();
    }
}
