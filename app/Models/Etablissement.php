<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etablissement extends Model
{
    protected $fillable = ['nom'];

    public function parcours(): HasMany
    {
        return $this->hasMany(Parcours::class);
    }
}
