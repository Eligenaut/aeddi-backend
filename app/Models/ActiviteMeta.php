<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiviteMeta extends Model
{
    protected $table = 'activite_meta';

    protected $fillable = [
        'activite_id',
        'meta_key',
        'meta_value',
    ];

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }
}