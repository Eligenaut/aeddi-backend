<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Activite extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'lieu',
        'image_lieu',
        'categorie',
        'statut',
        'image',
        'meta',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'meta'       => AsArrayObject::class,
    ];

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