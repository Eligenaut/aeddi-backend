<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMeta;
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
        'create_parametre'
    ];

    // ✅ Permissions par défaut explicites
    private const DEFAULT_PERMISSIONS = [
        'show_activite',
        'show_cotisation',
        'show_membre',
        'show_parametre',
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

            // ✅ Appliquer aux utilisateurs concernés
            if ($role === 'MEMBER' || $role === 'NOVICE') {
                $users = User::where('role', $role)->get();
            } else {
                $users = User::where('role', $role)
                    ->whereJsonContains('sub_role', $subRole)
                    ->get();
            }

            foreach ($users as $user) {
                /** @var User $user */
                $user->setMeta('permissions', json_encode($permissions));
            }

            return response()->json([
                'success' => true,
                'message' => 'Permissions définies et appliquées avec succès',
                'data' => [
                    'role'          => $role,
                    'subRole'       => $subRole,
                    'permissions'   => $permissions,
                    'users_updated' => $users->count(),
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

            // ✅ Permissions par défaut
            $defaultPermissions = self::DEFAULT_PERMISSIONS;

            // ✅ Sauvegarder dans role_permissions
            RolePermission::updateOrCreate(
                ['role' => $role, 'sub_role' => $subRole],
                ['permissions' => $defaultPermissions]
            );

            // ✅ Appliquer aux utilisateurs concernés
            if ($role === 'MEMBER' || $role === 'NOVICE') {
                $users = User::where('role', $role)->get();
            } else {
                $users = User::where('role', $role)
                    ->whereJsonContains('sub_role', $subRole)
                    ->get();
            }

            foreach ($users as $user) {
                /** @var User $user */
                $user->setMeta('permissions', json_encode($defaultPermissions));
            }

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

            $permissions = RolePermission::findPermissions($role, $subRole ?? null);

            return response()->json([
                'success' => true,
                'data' => [
                    'role'        => $role,
                    'subRole'     => $subRole ?? null,
                    'permissions' => $permissions ?? [],
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

    // ✅ GET /permissions/{userId}
    public function getPermissions($userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $permissions = json_decode($user->getMeta('permissions'), true) ?? [];

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id'     => $userId,
                    'role'        => $user->role,
                    'subRoles'    => json_decode($user->sub_role) ?? [],
                    'permissions' => $permissions,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des permissions',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE /permissions/{userId}
    public function deletePermissions($userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            UserMeta::where('user_id', $userId)
                ->where('meta_key', 'permissions')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permissions supprimées avec succès',
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des permissions',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET /permissions/{userId}/has/{permission}
    public function hasPermission($userId, $permission): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $permissions   = json_decode($user->getMeta('permissions'), true) ?? [];
            $hasPermission = in_array($permission, $permissions);

            return response()->json([
                'success'       => true,
                'hasPermission' => $hasPermission,
                'permission'    => $permission,
                'user_id'       => $userId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la permission',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
