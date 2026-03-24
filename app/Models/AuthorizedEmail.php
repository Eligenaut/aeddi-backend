<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class AuthorizedEmail extends Model
{
    protected $table = 'authorized_emails';

    protected $fillable = [
        'user_id',
        'email',
        'role',
        'meta',
    ];

    protected $casts = [
        'meta' => AsArrayObject::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
