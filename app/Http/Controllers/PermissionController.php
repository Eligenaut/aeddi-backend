<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Liste des rôles valides
     */
    private const VALID_ROLES = ['BUREAU', 'MEMBER'];

    /**
     * Liste des sous-rôles valides
     */
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

    /**
     * Liste des permissions valides
     */
    private const VALID_PERMISSIONS = [
        'show_activite', 'edit_activite', 'delete_activite', 'create_activite',
        'show_cotisation', 'edit_cotisation', 'delete_cotisation', 'create_cotisation',
        'show_membre', 'edit_membre', 'delete_membre', 'create_membre',
        'show_parametre', 'edit_parametre', 'delete_parametre', 'create_parametre'
    ];

    /**
     * Ajouter/Mettre à jour les permissions d'un utilisateur
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function addPermission(Request $request, $userId): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:' . implode(',', self::VALID_ROLES),
                'subRoles' => 'required|array',
                'subRoles.*' => 'string|in:' . implode(',', self::VALID_SUB_ROLES),
                'permissions' => 'required|array',
                'permissions.*' => 'string|in:' . implode(',', self::VALID_PERMISSIONS)
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Vérifier que l'utilisateur existe
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Validation supplémentaire : si BUREAU, doit avoir au moins 1 subRole
            if ($validated['role'] === 'BUREAU' && empty($validated['subRoles'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un membre du bureau doit avoir au moins une fonction (subRole)'
                ], 422);
            }

            // Validation supplémentaire : si MEMBER, subRoles doit être vide
            if ($validated['role'] === 'MEMBER' && !empty($validated['subRoles'])) {
                $validated['subRoles'] = [];
            }

            // Préparer les données à sauvegarder
            $permissionData = [
                'role' => $validated['role'],
                'subRoles' => $validated['subRoles'],
                'permissions' => array_unique($validated['permissions']), // Éviter les doublons
                'updatedAt' => now()->toISOString()
            ];

            // Sauvegarder le rôle principal
            UserMeta::updateOrCreate(
                [
                    'user_id' => $userId,
                    'meta_key' => 'role'
                ],
                [
                    'meta_value' => $validated['role']
                ]
            );

            // Sauvegarder les sous-rôles
            UserMeta::updateOrCreate(
                [
                    'user_id' => $userId,
                    'meta_key' => 'sub_roles'
                ],
                [
                    'meta_value' => json_encode($validated['subRoles'])
                ]
            );

            // Sauvegarder les permissions
            UserMeta::updateOrCreate(
                [
                    'user_id' => $userId,
                    'meta_key' => 'permissions'
                ],
                [
                    'meta_value' => json_encode($validated['permissions'])
                ]
            );

            // Optionnel : sauvegarder tout dans une seule meta_value pour faciliter la récupération
            UserMeta::updateOrCreate(
                [
                    'user_id' => $userId,
                    'meta_key' => 'permission_data'
                ],
                [
                    'meta_value' => json_encode($permissionData)
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Permissions mises à jour avec succès',
                'data' => [
                    'user_id' => $userId,
                    'role' => $validated['role'],
                    'subRoles' => $validated['subRoles'],
                    'permissions' => $validated['permissions'],
                    'totalPermissions' => count($validated['permissions'])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde des permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les permissions d'un utilisateur
     * @param int $userId
     * @return JsonResponse
     */
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

            // Récupérer les données de permission
            $permissionData = UserMeta::where('user_id', $userId)
                ->where('meta_key', 'permission_data')
                ->first();

            if (!$permissionData || !$permissionData->meta_value) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucune permission trouvée',
                    'data' => [
                        'user_id' => $userId,
                        'role' => null,
                        'subRoles' => [],
                        'permissions' => []
                    ]
                ]);
            }

            $data = json_decode($permissionData->meta_value, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'role' => $data['role'] ?? null,
                    'subRoles' => $data['subRoles'] ?? [],
                    'permissions' => $data['permissions'] ?? [],
                    'updatedAt' => $data['updatedAt'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer toutes les permissions d'un utilisateur
     * @param int $userId
     * @return JsonResponse
     */
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

            // Supprimer toutes les meta_keys liées aux permissions
            UserMeta::where('user_id', $userId)
                ->whereIn('meta_key', ['role', 'sub_roles', 'permissions', 'permission_data'])
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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un utilisateur a une permission spécifique
     * @param int $userId
     * @param string $permission
     * @return JsonResponse
     */
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

            $permissionData = UserMeta::where('user_id', $userId)
                ->where('meta_key', 'permission_data')
                ->first();

            if (!$permissionData || !$permissionData->meta_value) {
                return response()->json([
                    'success' => true,
                    'hasPermission' => false,
                    'message' => 'Aucune permission trouvée pour cet utilisateur'
                ]);
            }

            $data = json_decode($permissionData->meta_value, true);
            $permissions = $data['permissions'] ?? [];
            $hasPermission = in_array($permission, $permissions);

            return response()->json([
                'success' => true,
                'hasPermission' => $hasPermission,
                'permission' => $permission,
                'user_id' => $userId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
