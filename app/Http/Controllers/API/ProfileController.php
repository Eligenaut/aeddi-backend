<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Récupérer les informations du profil de l'utilisateur connecté
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role,
                'sub_role' => $user->sub_role,
                'etablissement' => $user->etablissement,
                'parcours' => $user->parcours,
                'niveau' => $user->niveau,
                'promotion' => $user->promotion,
                'logement' => $user->logement,
                'blocCampus' => $user->bloc_campus,
                'quartier' => $user->quartier,
                'telephone' => $user->telephone,
                'image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'statut' => $user->email_verified_at ? 'actif' : 'en_attente',
                'created_at' => $user->created_at->toDateTimeString(),
                'updated_at' => $user->updated_at->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Mettre à jour le profil de l'utilisateur connecté
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:20',
            'etablissement' => 'nullable|string|max:255',
            'parcours' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:50',
            'promotion' => 'nullable|string|max:10',
            'logement' => 'nullable|in:campus,ville',
            'bloc_campus' => 'nullable|required_if:logement,campus|string|max:50',
            'quartier' => 'nullable|required_if:logement,ville|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Mise à jour des champs de base
            $user->fill($validated);

            // Gestion de l'image de profil
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile-images', 'public');
                $user->profile_image = $path;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'role' => $user->role,
                    'sub_role' => $user->sub_role,
                    'etablissement' => $user->etablissement,
                    'parcours' => $user->parcours,
                    'niveau' => $user->niveau,
                    'promotion' => $user->promotion,
                    'logement' => $user->logement,
                    'blocCampus' => $user->bloc_campus,
                    'quartier' => $user->quartier,
                    'telephone' => $user->telephone,
                    'image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
