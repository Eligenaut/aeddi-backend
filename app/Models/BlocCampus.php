<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlocCampus extends Model
{
    protected $table = 'blocs_campus';

    protected $fillable = ['section_campus_id', 'nom'];

    public function sectionCampus(): BelongsTo
    {
        return $this->belongsTo(SectionCampus::class);
    }
}
