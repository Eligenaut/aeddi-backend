<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcours extends Model
{
    protected $fillable = ['etablissement_id', 'nom'];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }
}
