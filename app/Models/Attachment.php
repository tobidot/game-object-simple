<?php

namespace App\Models;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Attachment
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $path
 * @property PublishState $publish_state
 * @property AttachmentType $type
 * @property string|null $file_name
 * @method static Builder|Attachment newModelQuery()
 * @method static Builder|Attachment newQuery()
 * @method static Builder|Attachment query()
 * @method static Builder|Attachment whereCreatedAt($value)
 * @method static Builder|Attachment whereId($value)
 * @method static Builder|Attachment wherePath($value)
 * @method static Builder|Attachment wherePublishState($value)
 * @method static Builder|Attachment whereType($value)
 * @method static Builder|Attachment whereUpdatedAt($value)
 * @property string $url
 * @method static Builder|Attachment whereUrl($value)
 * @property-read Collection<int, CodeRelease> $codeReleases
 * @property-read int|null $code_releases_count
 * @mixin Eloquent
 */
class Attachment extends Model
{
    use HasFactory;

    protected $casts = [
        'publish_state' => PublishState::class,
        'type' => AttachmentType::class,
    ];

    public function codeReleases(): MorphToMany
    {
        return $this->morphedByMany(CodeRelease::class, 'attachable');
    }
}
