<?php

namespace App\Helpers;

use App\Models\RolePermission;
use App\Models\User;

class Permissions
{
    // ── Toutes les permissions disponibles ──────────────────────────────
    const ALL = [
        'show_activite',   'edit_activite',   'delete_activite',   'create_activite',
        'show_cotisation', 'edit_cotisation',  'delete_cotisation', 'create_cotisation',
        'show_membre',     'edit_membre',      'delete_membre',     'create_membre',
        'show_parametre',  'edit_parametre',   'delete_parametre',  'create_parametre',
        'show_tache',      'edit_tache',       'delete_tache',      'create_tache',
    ];

    // ── Permissions de base par rôle (utilisées pour le seed / le reset) ──
    const DEFAULTS = [
        'NOVICE' => [
            'show_activite',
            'show_cotisation',
            'show_membre',
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
            'show_tache',
        ],
    ];

    // ── Permissions effectives d'un utilisateur ─────────────────────────
    // Source de vérité unique : table role_permissions.
    // Aucun fallback : un utilisateur n'a que ce qui est explicitement
    // configuré pour son rôle (+ sub_role). Pas de configuration = accès
    // refusé. Seul l'admin a tout.
    public static function resolve(User $user): array
    {
        if ($user->isAdmin()) {
            return self::ALL;
        }

        $role = strtoupper((string) $user->role);
        $subRole = self::firstSubRole($user->sub_role);

        // Le Président du Bureau a toutes les permissions (comme l'admin)
        if ($subRole === 'PRESIDENT') {
            return self::ALL;
        }

        // 1) configuration exacte (rôle + sous-rôle)
        if ($subRole !== null) {
            $custom = RolePermission::findPermissions($role, $subRole);
            if (!empty($custom)) {
                return $custom;
            }
        }

        // 2) configuration générique (rôle seul)
        return RolePermission::findPermissions($role, null);
    }

    // ── Vérifier si un user a une permission ────────────────────────────
    public static function userHas(User $user, string $permission): bool
    {
        return in_array($permission, self::resolve($user));
    }

    // ── Premier sous-rôle (le champ sub_role est un JSON encodé) ────────
    public static function firstSubRole(mixed $subRole): ?string
    {
        if (is_string($subRole)) {
            $decoded = json_decode($subRole, true);
            if (is_array($decoded)) {
                $subRole = $decoded;
            }
        }

        if (is_array($subRole) && count($subRole) > 0) {
            $first = reset($subRole);
            return is_string($first) && $first !== '' ? $first : null;
        }

        return null;
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
