<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotisation extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'montant',
        'date_debut',
        'date_fin',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2'
    ];

    /**
     * Relation avec les cotisations des membres
     */
    public function cotisationMembres(): HasMany
    {
        return $this->hasMany(CotisationMembre::class);
    }

    /**
     * Relation avec les membres via la table pivot
     */
    public function membres()
    {
        return $this->belongsToMany(User::class, 'cotisation_membre')
                    ->withPivot('statut', 'montant_restant')
                    ->withTimestamps();
    }
}
