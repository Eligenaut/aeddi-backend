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
    private const VALID_ROLES = ['BUREAU', 'MEMBER'];

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

    // ✅ POST /permissions/add
    // Définir les permissions pour un role/subRole dans role_permissions
    // ET les appliquer à tous les utilisateurs qui ont ce role/subRole
    public function addPermission(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role'          => 'required|string|in:' . implode(',', self::VALID_ROLES),
                'subRoles'      => 'nullable|array',
                'subRoles.*'    => 'string|in:' . implode(',', self::VALID_SUB_ROLES),
                'permissions'   => 'required|array',
                'permissions.*' => 'string|in:' . implode(',', self::VALID_PERMISSIONS)
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
            $subRoles    = $role === 'BUREAU' ? ($validated['subRoles'] ?? []) : [null];
            $permissions = array_unique($validated['permissions']);

            $usersUpdated = 0;

            foreach ($subRoles as $subRole) {

                // ✅ Sauvegarder dans role_permissions
                RolePermission::updateOrCreate(
                    ['role' => $role, 'sub_role' => $subRole],
                    ['permissions' => $permissions]
                );

                // ✅ Appliquer à tous les utilisateurs qui ont ce role/subRole
                if ($role === 'MEMBER') {
                    $users = User::where('role', 'MEMBER')->get();
                } else {
                    $users = User::where('role', $role)
                        ->whereJsonContains('sub_role', $subRole)
                        ->get();
                }

                foreach ($users as $user) {
                    /** @var User $user */
                    $user->setMeta('permissions', json_encode($permissions));
                }

                $usersUpdated += $users->count();
            }

            return response()->json([
                'success' => true,
                'message' => 'Permissions définies et appliquées avec succès',
                'data' => [
                    'role'          => $role,
                    'subRoles'      => $subRoles,
                    'permissions'   => $permissions,
                    'users_updated' => $usersUpdated,
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

    // Récupérer les permissions d'un role/subRole
    public function getRolePermissions(Request $request): JsonResponse
    {
        try {
            $role    = $request->query('role');
            $subRole = $request->query('subRole');

            $permissions = RolePermission::findPermissions($role, $subRole);

            return response()->json([
                'success'     => true,
                'data' => [
                    'role'        => $role,
                    'subRole'     => $subRole,
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
