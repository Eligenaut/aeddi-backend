<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nom',
        'prenom',
        'email',
        'password',
        'email_verified_at',
        'google_id',
        'avatar',
        'role',
        'sub_role',
        'etablissement',
        'parcours',
        'niveau',
        'promotion',
        'logement',
        'bloc_campus',
        'quartier',
        'telephone',
        'profile_image',
        'verification_code',
        'verification_code_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation avec les cotisations du membre
     */
    public function cotisationMembres(): HasMany
    {
        return $this->hasMany(CotisationMembre::class);
    }

    /**
     * Relation avec les cotisations via la table pivot
     */
    public function cotisations()
    {
        return $this->belongsToMany(Cotisation::class, 'cotisation_membre')
                    ->withPivot('statut', 'montant_restant')
                    ->withTimestamps();
    }
}
