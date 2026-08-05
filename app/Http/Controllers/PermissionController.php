<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    private const VALID_ROLES = ['NOVICE', 'BUREAU', 'MEMBER'];

    private const VALID_SUB_ROLES = [
        'PRESIDENT',
        'VICE_PRESIDENT',
        'TRESORIER',
        'VICE_TRESORIER',
        'COMMISSAIRE_COMPTE',
        'COMMISSION_CERCLE_ETUDE',
        'COMMISSION_INFORMATIQUE',
        'COMMISSION_LOGEMENT',
        'COMMISSION_SOCIAL',
        'COMMISSION_FETE',
        'COMMISSION_SPORT',
        'COMMISSION_COMMUNICATION',
        'COMMISSION_ENVIRONNEMENT'
    ];

    private const VALID_PERMISSIONS = [
        'show_activite',
        'edit_activite',
        'delete_activite',
        'create_activite',
        'show_cotisation',
        'edit_cotisation',
        'delete_cotisation',
        'create_cotisation',
        'show_membre',
        'edit_membre',
        'delete_membre',
        'create_membre',
        'show_parametre',
        'edit_parametre',
        'delete_parametre',
        'create_parametre',
        'show_tache',
        'edit_tache',
        'delete_tache',
        'create_tache'
    ];

    // ✅ POST /permissions/add
    public function addPermission(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role'          => 'required|string|in:' . implode(',', self::VALID_ROLES),
                'subRole'       => 'nullable|string|in:' . implode(',', self::VALID_SUB_ROLES),
                'permissions'   => 'present|array',
                'permissions.*' => 'string|in:' . implode(',', self::VALID_PERMISSIONS),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validated   = $validator->validated();
            $role        = $validated['role'];
            $subRole     = $validated['subRole'] ?? null;
            $permissions = array_unique($validated['permissions']);

            // ✅ Sauvegarder dans role_permissions
            RolePermission::updateOrCreate(
                ['role' => $role, 'sub_role' => $subRole],
                ['permissions' => $permissions]
            );

            return response()->json([
                'success' => true,
                'message' => 'Permissions définies avec succès',
                'data' => [
                    'role'        => $role,
                    'subRole'     => $subRole,
                    'permissions' => $permissions,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ✅ POST /permissions/reset
    public function resetPermissions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role'    => 'required|string|in:' . implode(',', self::VALID_ROLES),
                'subRole' => 'nullable|string|in:' . implode(',', self::VALID_SUB_ROLES),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $role      = $validated['role'];
            $subRole   = $validated['subRole'] ?? null;

            // ✅ Permissions de base du rôle (source unique : Permissions::DEFAULTS)
            // Le Président du Bureau a toutes les permissions (comme l'admin)
            if (strtoupper($role) === 'BUREAU' && strtoupper((string) $subRole) === 'PRESIDENT') {
                $defaultPermissions = \App\Helpers\Permissions::ALL;
            } else {
                $defaultPermissions = \App\Helpers\Permissions::DEFAULTS[$role] ?? [];
            }

            // ✅ Sauvegarder dans role_permissions
            RolePermission::updateOrCreate(
                ['role' => $role, 'sub_role' => $subRole],
                ['permissions' => $defaultPermissions]
            );

            return response()->json([
                'success' => true,
                'message' => 'Permissions réinitialisées avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET /permissions/get?role=BUREAU&subRole=PRESIDENT
    public function getRolePermissions(Request $request): JsonResponse
    {
        try {
            $role    = $request->query('role');
            $subRole = $request->query('subRole');

            if (empty($role) || !in_array($role, self::VALID_ROLES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rôle invalide ou manquant',
                ], 422);
            }

            if (!empty($subRole) && !in_array($subRole, self::VALID_SUB_ROLES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'SubRôle invalide',
                ], 422);
            }

            $permissions = [];
            $role        = strtoupper($role);

            // Permissions effectives : ligne exacte (rôle + sous-rôle) puis ligne générique (rôle seul)
            if (!empty($subRole)) {
                $permissions = RolePermission::findPermissions($role, $subRole);
            }
            if (empty($permissions)) {
                $permissions = RolePermission::findPermissions($role, null);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'role'        => $role,
                    'subRole'     => $subRole ?? null,
                    'permissions' => $permissions,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
