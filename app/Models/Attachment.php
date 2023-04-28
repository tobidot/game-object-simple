<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int $publish_state_id
 * @property int $type_id
 * @method static Builder|Attachment newModelQuery()
 * @method static Builder|Attachment newQuery()
 * @method static Builder|Attachment query()
 * @method static Builder|Attachment whereCreatedAt($value)
 * @method static Builder|Attachment whereId($value)
 * @method static Builder|Attachment wherePath($value)
 * @method static Builder|Attachment wherePublishStateId($value)
 * @method static Builder|Attachment whereTypeId($value)
 * @method static Builder|Attachment whereUpdatedAt($value)
 * @property string $url
 * @method static Builder|Attachment whereUrl($value)
 * @mixin Eloquent
 */
class Attachment extends Model
{
    use HasFactory;

    public function code_releases(): MorphToMany
    {
        return $this->morphedByMany(CodeRelease::class, 'attachable');
    }
}
