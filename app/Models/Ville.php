<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $fillable = ['nom'];

    public function quartiers()
    {
        return $this->hasMany(Quartier::class);
    }
}
