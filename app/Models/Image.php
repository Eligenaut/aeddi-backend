<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = ['path', 'titre', 'ordre'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Helper URL complète ───────────────────────────────────
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}