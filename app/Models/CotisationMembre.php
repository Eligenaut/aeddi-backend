<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotisationMembre extends Model
{
    protected $table = 'cotisation_membre';

    protected $fillable = [
        'user_id',
        'cotisation_id',
        'statut',
        'montant_restant'
    ];

    protected $casts = [
        'montant_restant' => 'decimal:2'
    ];

    /**
     * Relation avec l'utilisateur (membre)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la cotisation
     */
    public function cotisation(): BelongsTo
    {
        return $this->belongsTo(Cotisation::class);
    }
}
