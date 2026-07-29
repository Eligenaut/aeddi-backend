<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogement extends Model
{
    protected $fillable = [
        'user_id',
        'type_logement_id',
        'option_campus_id',
        'section_campus_id',
        'bloc_campus_id',
        'quartier_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLogement(): BelongsTo
    {
        return $this->belongsTo(TypeLogement::class);
    }

    public function optionCampus(): BelongsTo
    {
        return $this->belongsTo(OptionCampus::class);
    }

    public function sectionCampus(): BelongsTo
    {
        return $this->belongsTo(SectionCampus::class);
    }

    public function blocCampus(): BelongsTo
    {
        return $this->belongsTo(BlocCampus::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }
}
