<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Page
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $uri
 * @property string $title
 * @property string $content
 * @property int $publish_state_id
 * @property-read Collection<int, LogEvent> $logEvents
 * @property-read LuPublishState $publishState
 * @method static Builder|Page newModelQuery()
 * @method static Builder|Page newQuery()
 * @method static Builder|Page query()
 * @method static Builder|Page whereContent($value)
 * @method static Builder|Page whereCreatedAt($value)
 * @method static Builder|Page whereId($value)
 * @method static Builder|Page wherePublishStateId($value)
 * @method static Builder|Page whereTitle($value)
 * @method static Builder|Page whereUpdatedAt($value)
 * @method static Builder|Page whereUri($value)
 * @mixin Eloquent
 */
class Page extends Model
{
    use HasFactory;


    public $with = [
        'publishState'
    ];

    public function getRouteKeyName() : string
    {
        return 'uri';
    }

    public function publishState() : BelongsTo {
        return $this->belongsTo(LuPublishState::class);
    }

    public function logEvents(): MorphMany {
        return $this->morphMany(LogEvent::class,'loggable' );
    }
}
