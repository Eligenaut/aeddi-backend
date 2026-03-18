<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activite extends Model
{
    protected $fillable = [
        'statut',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function metas(): HasMany
    {
        return $this->hasMany(ActiviteMeta::class);
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activite_membre')
                    ->withPivot('statut_participation', 'commentaire')
                    ->withTimestamps();
    }

    public function activiteMembres(): HasMany
    {
        return $this->hasMany(ActiviteMembre::class);
    }

    // ─── Helpers meta ─────────────────────────────────────────

    public function getMeta(string $key, mixed $default = null): mixed
    {
        $meta = $this->metas->firstWhere('meta_key', $key);
        return $meta ? $meta->meta_value : $default;
    }

    public function setMeta(string $key, mixed $value): void
    {
        $this->metas()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function setMetas(array $metas): void
    {
        foreach ($metas as $key => $value) {
            $this->setMeta($key, $value);
        }
    }

    public function deleteMeta(string $key): void
    {
        $this->metas()->where('meta_key', $key)->delete();
    }
}