<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Models\Page
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $uri
 * @property string $title
 * @property string $content
 * @property int $publish_state_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LogEvent> $log_events
 * @property-read int|null $log_events_count
 * @property-read \App\Models\LuPublishState $publish_state
 * @method static \Illuminate\Database\Eloquent\Builder|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page wherePublishStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereUri($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LogEvent> $log_events
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LogEvent> $log_events
 * @mixin \Eloquent
 */
class Page extends Model
{
    use HasFactory;


    public $with = [
        'publish_state'
    ];

    public function getRouteKeyName() : string
    {
        return 'uri';
    }

    public function publish_state() : BelongsTo {
        return $this->belongsTo(LuPublishState::class);
    }

    public function log_events(): MorphMany {
        return $this->morphMany(LogEvent::class,'loggable' );
    }
}
