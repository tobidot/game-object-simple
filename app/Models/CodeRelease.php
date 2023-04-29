<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laravel\Nova\Fields\MorphedByMany;

/**
 * App\Models\CodeRelease
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $version
 * @property float $completeness
 * @property float $fun
 * @property float $complexity
 * @property int $project_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @method static \Database\Factories\CodeReleaseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease query()
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereCompleteness($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereComplexity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereFun($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CodeRelease whereVersion($value)
 * @mixin \Eloquent
 */
class CodeRelease extends Model
{
    use HasFactory;

    public function code(): Attachment
    {
        return $this->attachments()
            ->wherePivot('relation', 'code')
            ->first();
    }

    public function attachments() : MorphToMany {
        return $this->morphToMany(Attachment::class, 'attachable');
    }
}
