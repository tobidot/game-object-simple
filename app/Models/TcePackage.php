<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\TcePackage
 *
 * The v3 data model for a published Tobidot Custom Element or library — see the "Publishing &
 * Discovery: Data Model" specification. Namespace is real, required data (never defaulted to
 * 'tobidot' in application code).
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

    /**
     * A dependency points at one version's row and goes with it (cascade), so dependents move to the
     * newest remaining version first. Only the row and its links go here: `StorageCleaner` frees the files.
     */
    protected static function booted(): void
    {
        static::deleting(function (TcePackage $package): void {
            $survivor = $package->otherVersions()
                ->orderByDesc('major')
                ->orderByDesc('minor')
                ->orderByDesc('patch')
                ->first();
            if ($survivor !== null) {
                DB::table('tce_package_dependencies')
                    ->where('dependency_id', $package->id)
                    ->update(['dependency_id' => $survivor->id]);
            }
            $package->attachments()->detach();
        });
    }

    /**
     * @return Builder<TcePackage>
     */
    public function otherVersions(): Builder
    {
        return static::query()
            ->where('namespace', $this->namespace)
            ->where('name', $this->name)
            ->whereKeyNot($this->getKey());
    }

    public function hasOtherVersions(): bool
    {
        return $this->otherVersions()->exists();
    }

    public function hasDependents(): bool
    {
        return DB::table('tce_package_dependencies')->where('dependency_id', $this->id)->exists();
    }

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
