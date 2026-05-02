<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\CodeReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string $name
 * @property string $description
 * @property string $kind
 *
 * @property int $major
 * @property int $minor
 * @property int $patch
 *
 * @method static \Database\Factories\TobidotElementFactory factory($count = null, $state = [])
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
 */
class TobidotElement extends Model
{
    use HasFactory;

    protected $casts = [
        'standalone' => 'boolean',
        'extra' => 'json',
    ];

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachable', 'attachables')
            ->withPivot('relation');
    }

    public function codeAttachment(): MorphToMany
    {
        return $this->attachments()->wherePivot('relation', 'code');
    }

    public function iconAttachment(): MorphToMany
    {
        return $this->attachments()->wherePivot('relation', 'icon');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(TobidotElement::class, 'tobidot_element_dependencies', 'tobidot_element_id', 'dependency_id')
            ->withPivot(['required_major', 'required_minor', 'required_patch'])
            ->withTimestamps();
    }
}
