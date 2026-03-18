<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'sub_role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public static function findPermissions(string $role, ?string $subRole = null): array
    {
        $record = self::where('role', $role)
                      ->where('sub_role', $subRole)
                      ->first();

        return $record?->permissions ?? [];
    }
}