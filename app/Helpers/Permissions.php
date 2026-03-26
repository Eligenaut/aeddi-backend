<?php

namespace App\Helpers;

use App\Models\RolePermission;

class Permissions
{
    // ── Toutes les permissions disponibles ──────────────────────────────
    const ALL = [
        'show_activite',   'edit_activite',   'delete_activite',   'create_activite',
        'show_cotisation', 'edit_cotisation',  'delete_cotisation', 'create_cotisation',
        'show_membre',     'edit_membre',      'delete_membre',     'create_membre',
        'show_parametre',  'edit_parametre',   'delete_parametre',  'create_parametre',
    ];

    // ── Permissions par défaut selon le rôle ────────────────────────────
    const DEFAULTS = [
        'NOVICE' => [
            'show_activite',
            'show_cotisation',
        ],
        'MEMBER' => [
            'show_activite',
            'show_cotisation',
            'show_membre',
        ],
        'BUREAU' => [
            'show_activite',
            'show_cotisation',
            'show_membre',
            'show_parametre',
        ],
    ];

    // ── Vérifier si un user a une permission ────────────────────────────
    public static function userHas(\App\Models\User $user, string $permission): bool
    {
        // Admin = tout autorisé
        if ($user->isAdmin()) return true;

        $role = strtoupper((string) ($user->role ?? ''));

        // 1) Permissions spécifiques utilisateur (meta)
        $metaPermissions = json_decode($user->getMeta('permissions'), true);
        $metaPermissions = is_array($metaPermissions) ? $metaPermissions : [];

        // 2) Permissions par défaut selon le rôle (fallback)
        $defaultPermissions = self::DEFAULTS[$role] ?? [];

        // 3) Permissions définies par rôle/sub-rôle (source "role_permissions")
        // sub_role en DB = JSON string; on prend la 1ère valeur si présent, sinon null.
        $subRoles = json_decode($user->sub_role ?? '[]', true);
        $subRole = is_array($subRoles) && count($subRoles) > 0 ? (string) $subRoles[0] : null;
        $rolePermissions = [];
        try {
            $rolePermissions = RolePermission::findPermissions($role, $subRole);
        } catch (\Throwable $e) {
            // On reste permissif côté erreur, en s'appuyant sur defaults/meta.
            $rolePermissions = [];
        }

        $all = array_values(array_unique(array_merge(
            $defaultPermissions,
            $rolePermissions,
            $metaPermissions,
        )));

        return in_array($permission, $all, true);
    }

    // ── Retourner une réponse JSON "refusé" ─────────────────────────────
    public static function denied(string $permission = '')
    {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé' . ($permission ? " : permission « $permission » requise" : ''),
        ], 403);
    }
}
