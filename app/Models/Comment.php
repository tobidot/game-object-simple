<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 *
 * @property int $id
 * @property string $author
 * @property string $email
 * @property string $title
 * @property string $content
 * @property bool $visible
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Model $commentable
 * @property mixed $commentable_type
 * @property int $commentable_id
 *
 */
class Comment extends Model
{
    use HasFactory;

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function commentable() : MorphTo
    {
        return $this->morphTo();
    }
}
