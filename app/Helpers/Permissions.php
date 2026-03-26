<?php

namespace App\Helpers;

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

        $permissions = json_decode($user->getMeta('permissions'), true) ?? [];
        return in_array($permission, $permissions);
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
