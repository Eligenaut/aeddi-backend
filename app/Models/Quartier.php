<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quartier extends Model
{
    protected $fillable = ['nom', 'ville_id'];

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }
}
