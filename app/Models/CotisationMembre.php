<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotisationMembre extends Model
{
    protected $table = 'cotisation_membre';

    protected $fillable = [
        'user_id',
        'cotisation_id',
        'statut',
        'montant_restant',
        'meta',
    ];

    protected $casts = [
        'montant_restant' => 'decimal:2',
        'meta'            => AsArrayObject::class,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cotisation(): BelongsTo
    {
        return $this->belongsTo(Cotisation::class);
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
