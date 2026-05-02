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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function tobidotElements(): MorphToMany
    {
        return $this->morphedByMany(TobidotElement::class, 'attachable');
    }
}
