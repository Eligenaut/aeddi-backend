<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tache extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'assigned_by',
        'statut',
        'priorite',
        'date_echeance',
    ];

    protected $casts = [
        'date_debut'   => 'date',
        'date_echeance' => 'date',
    ];

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tache_user');
    }
}
