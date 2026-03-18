<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotisationMeta extends Model
{
    protected $table = 'cotisation_meta';

    protected $fillable = [
        'cotisation_id',
        'meta_key',
        'meta_value',
    ];

    public function cotisation(): BelongsTo
    {
        return $this->belongsTo(Cotisation::class);
    }
}