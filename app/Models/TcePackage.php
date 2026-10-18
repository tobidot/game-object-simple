<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\TcePackage
 *
 * The v3 data model for a published Tobidot Custom Element or library — see the "Publishing &
 * Discovery: Data Model" specification. Namespace is real, required data (never defaulted to
 * 'tobidot' in application code); the legacy `TobidotElement` table is kept read-only and is
 * never a source for this model or anything that resolves from it.
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $namespace
 * @property string $name
 * @property string $kind
 * @property string|null $description
 * @property int $major
 * @property int $minor
 * @property int $patch
 * @property int $width
 * @property int $height
 * @property array|null $extra
 *
 * @method static \Illuminate\Database\Eloquent\Factories\Factory factory($count = null, $state = [])
 * @method static Builder|TcePackage newModelQuery()
 * @method static Builder|TcePackage newQuery()
 * @method static Builder|TcePackage query()
 *
 * @mixin Eloquent
 */
class TcePackage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
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
        return $this->belongsToMany(TcePackage::class, 'tce_package_dependencies', 'tce_package_id', 'dependency_id')
            ->withPivot(['required_major', 'required_minor', 'required_patch'])
            ->withTimestamps();
    }
}
