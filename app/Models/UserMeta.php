<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    protected $table = 'user_meta';

    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}