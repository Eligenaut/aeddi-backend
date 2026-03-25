<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cotisation extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'montant_ancien',
        'montant_novice',
        'date_debut',
        'date_fin',
        'statut',
        'meta',
    ];

    protected $casts = [
        'date_debut'      => 'date',
        'date_fin'        => 'date',
        'montant_ancien'  => 'decimal:2',
        'montant_novice'  => 'decimal:2',
        'meta'            => AsArrayObject::class,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function cotisationMembres(): HasMany
    {
        return $this->hasMany(CotisationMembre::class);
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cotisation_membre')
            ->withPivot('statut', 'montant_restant')
            ->withTimestamps();
    }

    // ─── Helpers meta ─────────────────────────────────────────

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta?->offsetGet($key) ?? $default;
    }

    public function setMeta(string $key, mixed $value): void
    {
        $this->meta ??= new \ArrayObject();
        $this->meta[$key] = $value;
        $this->save();
    }

    public function setMetas(array $metas): void
    {
        $this->meta ??= new \ArrayObject();
        foreach ($metas as $key => $value) {
            $this->meta[$key] = $value;
        }
        $this->save();
    }

    public function deleteMeta(string $key): void
    {
        unset($this->meta[$key]);
        $this->save();
    }
}
