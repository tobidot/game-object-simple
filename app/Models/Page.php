<?php

namespace App\Models;

use App\Models\Scopes\VisibleScope;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string|null $thumbnail
 * @property-read int|null $log_events_count
 * @property-read Collection<int, Page> $pages
 * @property-read int|null $pages_count
 * @property-read Collection<int, Project> $projects
 * @property-read int|null $projects_count
 * @property-read Collection<int, Page> $relatingPages
 * @property-read int|null $related_pages_count
 * @method static PageFactory factory($count = null, $state = [])
 * @method static Builder|Page whereThumbnail($value)
 * @mixin Eloquent
 */
class Page extends Model
{
    use HasFactory;


    public $with = [
        'publishState'
    ];

    protected static function booted()
    {
        parent::booted();
        self::addGlobalScope(new VisibleScope());
    }

    public function getRouteKeyName(): string
    {
        return 'uri';
    }

    public function publishState(): BelongsTo
    {
        return $this->belongsTo(LuPublishState::class);
    }

    public function logEvents(): MorphMany
    {
        return $this->morphMany(LogEvent::class, 'loggable');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_page', 'page_id', 'related_page_id');
    }

    public function relatingPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_page', 'related_page_id', 'page_id');
    }
}
