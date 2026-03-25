<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RolePermission;
use App\Models\Cotisation;
use App\Models\CotisationMembre;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    // ─── Helper upload image Cloudinary ──────────────────────
    private function uploadImageToCloudinary(string $imageData, string $publicId): string
    {
        \Cloudinary\Configuration\Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'profile_');
        file_put_contents($tmpFile, $imageData);

        $api = new \Cloudinary\Api\Upload\UploadApi();
        $result = $api->upload($tmpFile, [
            'folder'    => 'aeddi/membres',
            'public_id' => $publicId,
            'overwrite' => true,
        ]);

        unlink($tmpFile);

        return $result['secure_url'];
    }

    // ─── Helper avatar URL ───────────────────────────────────
    private function getAvatarUrl(User $member): string
    {
        if ($member->avatar) {
            return $member->avatar;
        }
        if ($member->getMeta('profile_image')) {
            return asset('storage/' . $member->getMeta('profile_image'));
        }
        return '';
    }

    // ─── Formater les données d'un membre pour l'API ─────────
    private function formatMember(User $member): array
    {
        $cotisations = $member->cotisationMembres ?? collect();
        $cotisationStats = [
            'total'  => $cotisations->count(),
            'payees' => $cotisations->where('statut', 'paye')->count(),
        ];

        return [
            'id'               => $member->id,
            'name'             => $member->name ?? '',
            'nom'              => $member->getMeta('nom') ?? '',
            'prenom'           => $member->getMeta('prenom') ?? '',
            'email'            => $member->email ?? '',
            'avatar'           => $this->getAvatarUrl($member),
            'role'             => strtoupper($member->role ?? 'MEMBER'),
            'sub_role'         => json_decode($member->sub_role ?? '[]') ?? [],
            'etablissement'    => $member->getMeta('etablissement') ?? '',
            'parcours'         => $member->getMeta('parcours') ?? '',
            'niveau'           => $member->getMeta('niveau') ?? '',
            'promotion'        => $member->getMeta('promotion') ?? '',
            'logement'         => $member->getMeta('logement') ?? '',
            'bloc_campus'      => $member->getMeta('bloc_campus') ?? '',
            'quartier'         => $member->getMeta('quartier') ?? '',
            'telephone'        => $member->getMeta('telephone') ?? '',
            'statut'           => $member->email_verified_at ? 'actif' : 'en_attente',
            'cotisation_stats' => $cotisationStats,
            'created_at'       => $member->created_at->toDateTimeString(),
            'updated_at'       => $member->updated_at->toDateTimeString(),
        ];
    }

    // ─── Liste tous les membres ─────────────────────────────
    public function index(): JsonResponse
    {
        $members = User::where('role', '!=', 'ADMIN')
            ->with(['meta', 'cotisationMembres'])
            ->get();

        $data = $members->map(fn(User $member) => $this->formatMember($member));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'total'   => $data->count()
        ]);
    }

    // ─── Afficher un membre ──────────────────────────────────
    public function show($id): JsonResponse
    {
        $member = User::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }

        $member->load('meta');

        return response()->json([
            'success' => true,
            'data'    => $this->formatMember($member)
        ]);
    }

    // ─── Mettre à jour un membre ────────────────────────────
    public function update(Request $request, $id): JsonResponse
    {
        $member = User::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }

        if ($member->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Modification impossible pour l\'admin.'
            ], 403);
        }

        $validated = $request->validate([
            'nom'           => 'required|string|max:255',
            'prenom'        => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $id,
            'telephone'     => 'nullable|string|max:20',
            'etablissement' => 'nullable|string|max:255',
            'parcours'      => 'nullable|string|max:255',
            'niveau'        => 'nullable|string|max:255',
            'promotion'     => 'nullable|string|max:255',
            'logement'      => 'nullable|string|max:50',
            'blocCampus'    => 'nullable|string|max:255',
            'quartier'      => 'nullable|string|max:255',
            'image'         => 'nullable|string',
            'role'          => 'nullable|string|in:MEMBER,BUREAU',
            'subRoles'      => 'nullable|array',
            'subRoles.*'    => 'string',
        ]);

        try {
            $updateData = [
                'name'  => trim($validated['prenom'] . ' ' . $validated['nom']),
                'email' => $validated['email'],
            ];

            // Gestion des rôles et permissions
            if (isset($validated['role'])) {
                $newRole     = $validated['role'];
                $newSubRoles = $newRole === 'BUREAU' ? ($validated['subRoles'] ?? []) : [];
                $oldRole     = $member->role;
                $oldSubRole  = json_decode($member->sub_role ?? '[]', true)[0] ?? null;
                $newSubRole  = $newSubRoles[0] ?? null;

                if ($newRole !== $oldRole || $newSubRole !== $oldSubRole) {
                    $newPermissions = RolePermission::findPermissions($newRole, $newSubRole);
                    $member->setMeta('permissions', json_encode($newPermissions));
                }

                $updateData['role']     = $newRole;
                $updateData['sub_role'] = json_encode($newSubRoles);
            }

            $member->update($updateData);

            // Mise à jour des métas
            $metas = [
                'nom'           => $validated['nom'],
                'prenom'        => $validated['prenom'],
                'telephone'     => $validated['telephone']     ?? '',
                'etablissement' => $validated['etablissement'] ?? '',
                'parcours'      => $validated['parcours']      ?? '',
                'niveau'        => $validated['niveau']        ?? '',
                'promotion'     => $validated['promotion']     ?? '',
                'logement'      => $validated['logement']      ?? '',
                'bloc_campus'   => $validated['logement'] === 'campus' ? ($validated['blocCampus'] ?? '') : '',
                'quartier'      => $validated['logement'] === 'ville'  ? ($validated['quartier'] ?? '') : '',
            ];

            foreach ($metas as $key => $value) {
                $member->setMeta($key, $value);
            }

            // Upload avatar si fourni
            if (!empty($validated['image']) && str_starts_with($validated['image'], 'data:image/')) {
                $imageParts = explode(',', $validated['image']);
                if (count($imageParts) === 2) {
                    $imageData = base64_decode($imageParts[1], true);
                    $publicId  = 'profile_' . $member->id;
                    $avatarUrl = $this->uploadImageToCloudinary($imageData, $publicId);
                    $member->update(['avatar' => $avatarUrl]);
                }
            }

            $member->load('meta');

            return response()->json([
                'success' => true,
                'message' => 'Membre mis à jour avec succès',
                'data'    => $this->formatMember($member)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ─── Supprimer un membre ────────────────────────────────
    public function destroy($id): JsonResponse
    {
        $member = User::find($id);

        if (!$member || $member->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Suppression impossible.'
            ], 403);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Membre supprimé avec succès.'
        ]);
    }
}
