<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionCampus extends Model
{
    protected $table = 'sections_campus';

    protected $fillable = ['option_campus_id', 'nom'];

    public function optionCampus(): BelongsTo
    {
        return $this->belongsTo(OptionCampus::class);
    }

    public function blocs(): HasMany
    {
        return $this->hasMany(BlocCampus::class);
    }
}
