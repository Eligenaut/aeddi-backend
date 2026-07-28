<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeLogement extends Model
{
    protected $table = 'types_logement';

    protected $fillable = ['nom'];

    public function optionsCampus(): HasMany
    {
        return $this->hasMany(OptionCampus::class);
    }
}
