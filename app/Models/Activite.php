<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activite extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date'
    ];

    /**
     * Les membres associés à cette activité
     */
    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activite_membre')
                    ->withPivot('statut_participation', 'commentaire')
                    ->withTimestamps();
    }

    /**
     * Les relations pivot avec les membres
     */
    public function activiteMembres()
    {
        return $this->hasMany(ActiviteMembre::class);
    }
}
