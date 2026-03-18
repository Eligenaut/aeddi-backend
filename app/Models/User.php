<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
        'sub_role',
        'verification_code',
        'verification_code_expires_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    //Vérifier si l'utilisateur est admin
    public function isAdmin(): bool
    {
        return strtoupper($this->role) === 'ADMIN'
            && $this->email === 'admin@aeddi.com';
    }

    // Relation user_meta
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }

    // Helper pour récupérer une meta
    public function getMeta(string $key): ?string
    {
        if ($this->relationLoaded('meta')) {
            return $this->meta->where('meta_key', $key)->value('meta_value');
        }

        return $this->meta()->where('meta_key', $key)->value('meta_value');
    }

    public function setMeta(string $key, string $value): void
    {
        $this->meta()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }
}
