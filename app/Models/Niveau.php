<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Niveau extends Model
{
    protected $fillable = ['parcours_id', 'nom'];

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(Parcours::class);
    }
}
