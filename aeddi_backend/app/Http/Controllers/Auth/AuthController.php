<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cotisation;
use App\Models\CotisationMembre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Associer un utilisateur à toutes les cotisations actives
     */
    private function assignActiveCotisationsToUser($userId)
    {
        // Récupérer toutes les cotisations non terminées/annulées
        $activeCotisations = Cotisation::whereNotIn('statut', ['terminee', 'annulee'])->get();
        
        // Associer l'utilisateur à chaque cotisation active
        foreach ($activeCotisations as $cotisation) {
            CotisationMembre::firstOrCreate([
                'user_id' => $userId,
                'cotisation_id' => $cotisation->id,
            ], [
                'statut' => 'non_paye',
                'montant_restant' => $cotisation->montant
            ]);
        }
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'member', // Par défaut, tous les nouveaux utilisateurs sont des membres
        ]);

        // Associer le nouvel utilisateur à toutes les cotisations actives
        $this->assignActiveCotisationsToUser($user->id);

        // Créer un token Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion de l'utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $avatarUrl = $user->avatar;
        if (!$avatarUrl && $user->profile_image) {
            $avatarUrl = asset('storage/' . $user->profile_image);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name?? '',
                'nom' => $user->nom?? '',
                'prenom' => $user->prenom?? '',
                'email' => $user->email?? '',
                'avatar' => $avatarUrl?? '',
                'profile_image' => $user->profile_image?? '',
                'role' => $user->role ?? 'member',
                'sub_role' => $user->sub_role?? '',
                'etablissement' => $user->etablissement?? '',
                'parcours' => $user->parcours?? '',
                'niveau' => $user->niveau?? '',
                'promotion' => $user->promotion?? '',
                'logement' => $user->logement?? '',
                'bloc_campus' => $user->bloc_campus?? '',
                'quartier' => $user->quartier?? '',
                'telephone' => $user->telephone?? '',
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que l'avatar est une URL complète
        $avatarUrl = $user->avatar;
        if (!$avatarUrl && $user->profile_image) {
            $avatarUrl = asset('storage/' . $user->profile_image);
        }
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'avatar' => $avatarUrl,
                'role' => $user->role ?? 'member',
                'etablissement' => $user->etablissement,
                'parcours' => $user->parcours,
                'niveau' => $user->niveau,
                'promotion' => $user->promotion,
                'logement' => $user->logement,
                'bloc_campus' => $user->bloc_campus,
                'quartier' => $user->quartier,
                'telephone' => $user->telephone,
                'profile_image' => $user->profile_image,
            ],
        ]);
    }
}
