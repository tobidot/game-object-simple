<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Project
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $title
 * @property string $description
 * @property int $publish_state_is
 * @property int $state_id
 * @property-read Collection<int, CodeRelease> $codeReleases
 * @property-read int|null $code_releases_count
 * @method static Builder|Project newModelQuery()
 * @method static Builder|Project newQuery()
 * @method static Builder|Project query()
 * @method static Builder|Project whereCreatedAt($value)
 * @method static Builder|Project whereDescription($value)
 * @method static Builder|Project whereId($value)
 * @method static Builder|Project wherePublishStateIs($value)
 * @method static Builder|Project whereStateId($value)
 * @method static Builder|Project whereTitle($value)
 * @method static Builder|Project whereUpdatedAt($value)
 * @property int $publish_state_id
 * @method static Builder|Project wherePublishStateId($value)
 * @mixin Eloquent
 * @property string|null $thumbnail
 * @property-read Collection<int, Page> $pages
 * @property-read int|null $pages_count
 * @property-read Collection<int, Project> $projects
 * @property-read int|null $projects_count
 * @property-read Collection<int, Project> $relatingProjects
 * @property-read int|null $relating_projects_count
 * @method static ProjectFactory factory($count = null, $state = [])
 * @method static Builder|Project whereThumbnail($value)
 */
class Project extends Model
{
    use HasFactory;

    public function codeReleases() : HasMany
    {
        return $this->hasMany(CodeRelease::class);
    }

    public function projects() : BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_project', 'project_id', 'related_project_id');
    }

    public function pages() : BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function relatingProjects() : BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_project', 'related_project_id', 'project_id');
    }

}
