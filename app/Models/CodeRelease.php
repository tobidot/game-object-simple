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
