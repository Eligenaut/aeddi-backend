<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
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

    private function formatMember(User $member): array
    {
        return [
            'id'            => $member->id,
            'name'          => $member->name                        ?? '',
            'nom'           => $member->getMeta('nom')              ?? '',
            'prenom'        => $member->getMeta('prenom')           ?? '',
            'email'         => $member->email                       ?? '',
            'avatar'        => $this->getAvatarUrl($member),
            'role'          => strtoupper($member->role ?? 'MEMBER'),
            'sub_role'      => json_decode($member->sub_role ?? '[]') ?? [],
            'etablissement' => $member->getMeta('etablissement')    ?? '',
            'parcours'      => $member->getMeta('parcours')         ?? '',
            'niveau'        => $member->getMeta('niveau')           ?? '',
            'promotion'     => $member->getMeta('promotion')        ?? '',
            'logement'      => $member->getMeta('logement')         ?? '',
            'bloc_campus'   => $member->getMeta('bloc_campus')      ?? '',
            'quartier'      => $member->getMeta('quartier')         ?? '',
            'telephone'     => $member->getMeta('telephone')        ?? '',
            'statut'        => $member->email_verified_at ? 'actif' : 'en_attente',
            'created_at'    => $member->created_at->toDateTimeString(),
            'updated_at'    => $member->updated_at->toDateTimeString(),
        ];
    }

    public function index(): JsonResponse
    {
        $members = User::where('role', '!=', 'ADMIN')->get();
        $members->load('meta');

        $data = $members->map(fn(User $member) => $this->formatMember($member));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'total'   => $data->count()
        ]);
    }

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
            'imageName'     => 'nullable|string',
            'imageType'     => 'nullable|string',
            'role'          => 'nullable|string|in:MEMBER,BUREAU',
            'subRoles'      => 'nullable|array',
            'subRoles.*'    => 'string',
        ]);

        try {
            $updateData = [
                'name'  => trim($validated['prenom'] . ' ' . $validated['nom']),
                'email' => $validated['email'],
            ];

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
                'quartier'      => $validated['logement'] === 'ville'  ? ($validated['quartier']   ?? '') : '',
            ];

            foreach ($metas as $key => $value) {
                $member->setMeta($key, $value);
            }

            if (!empty($validated['image'])) {
                $imageBase64 = $validated['image'];
                if (str_starts_with($imageBase64, 'data:image/')) {
                    $imageParts = explode(',', $imageBase64);
                    if (count($imageParts) === 2) {
                        $imageData = base64_decode($imageParts[1], true);
                        $extension = 'jpg';
                        $imageType = $validated['imageType'] ?? '';

                        if (str_contains($imageType, 'png'))      $extension = 'png';
                        elseif (str_contains($imageType, 'webp')) $extension = 'webp';

                        $imagePath = 'profile_images/profile_' . $member->id . '.' . $extension;
                        Storage::disk('public')->put($imagePath, $imageData);
                        $member->update(['avatar' => asset('storage/' . $imagePath)]);
                    }
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