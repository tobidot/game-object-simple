<?php

namespace App\Models;

use App\Enums\LogEventTypes;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\LogEvent
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LogEventTypes $type
 * @property int $loggable_id
 * @property string $loggable_type
 * @property-read Model|Eloquent $loggable
 * @method static Builder|LogEvent newModelQuery()
 * @method static Builder|LogEvent newQuery()
 * @method static Builder|LogEvent query()
 * @method static Builder|LogEvent whereCreatedAt($value)
 * @method static Builder|LogEvent whereId($value)
 * @method static Builder|LogEvent whereLoggableId($value)
 * @method static Builder|LogEvent whereLoggableType($value)
 * @method static Builder|LogEvent whereType($value)
 * @method static Builder|LogEvent whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LogEvent extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => LogEventTypes::class,
    ];

    public function loggable() : MorphTo {
        return $this->morphTo();
    }
}
