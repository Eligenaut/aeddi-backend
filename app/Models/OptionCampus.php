<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionCampus extends Model
{
    protected $table = 'options_campus';

    protected $fillable = ['type_logement_id', 'nom'];

    public function typeLogement(): BelongsTo
    {
        return $this->belongsTo(TypeLogement::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SectionCampus::class);
    }
}
