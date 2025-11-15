<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiviteMembre extends Model
{
    protected $fillable = [
        'activite_id',
        'user_id',
        'statut_participation',
        'commentaire'
    ];

    /**
     * L'activité associée
     */
    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    /**
     * Le membre associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
