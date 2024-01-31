<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\CodeReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\MorphedByMany;

/**
 * App\Models\CodeRelease
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $version
 * @property float $completeness
 * @property float $fun
 * @property float $complexity
 * @property int $project_id
 * @property-read Collection<int, Attachment> $attachments
 * @property-read int|null $attachments_count
 * @method static CodeReleaseFactory factory($count = null, $state = [])
 * @method static Builder|CodeRelease newModelQuery()
 * @method static Builder|CodeRelease newQuery()
 * @method static Builder|CodeRelease query()
 * @method static Builder|CodeRelease whereCompleteness($value)
 * @method static Builder|CodeRelease whereComplexity($value)
 * @method static Builder|CodeRelease whereCreatedAt($value)
 * @method static Builder|CodeRelease whereFun($value)
 * @method static Builder|CodeRelease whereId($value)
 * @method static Builder|CodeRelease whereProjectId($value)
 * @method static Builder|CodeRelease whereUpdatedAt($value)
 * @method static Builder|CodeRelease whereVersion($value)
 * @mixin Eloquent
 * @property-read \App\Models\Project $project
 * @mixin \Eloquent
 */
class CodeRelease extends Model
{
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function code(): ?Attachment
    {
        return $this->attachments()
            ->wherePivot('relation', 'code')
            ->first();
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }
}
