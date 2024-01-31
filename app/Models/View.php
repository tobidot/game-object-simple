<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $url
 * @property DateTime $date
 * @property int $viewable_id
 * @property string $viewable_type
 * @property-read Model $viewable
 */
class View extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'datetime',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
