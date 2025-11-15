<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cotisation;
use App\Models\CotisationMembre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use GuzzleHttp\Client;

class GoogleController extends Controller
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
     * Rediriger l'utilisateur vers Google pour l'authentification
     */
    public function redirectToGoogle()
    {
        // Créer un client HTTP personnalisé pour ignorer SSL en développement
        $httpClient = null;
        if (config('app.env') === 'local') {
            $httpClient = new Client([
                'verify' => false, // Désactiver la vérification SSL
                'timeout' => 30,
            ]);
        }
        
        // Créer le provider Google avec le client HTTP personnalisé
        $provider = new GoogleProvider(
            request(),
            config('services.google.client_id'),
            config('services.google.client_secret'),
            config('services.google.redirect')
        );
        
        if ($httpClient) {
            $provider->setHttpClient($httpClient);
        }
        
        return $provider->redirect();
    }

    /**
     * Gérer le callback de Google après l'authentification
     */
    public function handleGoogleCallback()
    {
        try {
            // Créer un client HTTP personnalisé pour ignorer SSL en développement
            $httpClient = null;
            if (config('app.env') === 'local') {
                $httpClient = new Client([
                    'verify' => false, // Désactiver la vérification SSL
                    'timeout' => 30,
                ]);
            }
            
            // Créer le provider Google avec le client HTTP personnalisé
            $provider = new GoogleProvider(
                request(),
                config('services.google.client_id'),
                config('services.google.client_secret'),
                config('services.google.redirect')
            );
            
            if ($httpClient) {
                $provider->setHttpClient($httpClient);
            }
            
            $googleUser = $provider->user();

            // Chercher l'utilisateur par google_id ou email
            $user = User::where('google_id', $googleUser->id)
                       ->orWhere('email', $googleUser->email)
                       ->first();

            if (!$user) {
                // Rediriger vers une page spéciale du frontend
                $frontendUrl = 'http://localhost:3000/auth/google/notfound';
                $params = http_build_query([
                    'email' => $googleUser->email
                ]);
                return redirect($frontendUrl . '?' . $params);
            }

            // Mettre à jour les informations Google si nécessaire
            $updateData = [];
            
            if (!$user->google_id) {
                $updateData['google_id'] = $googleUser->id;
            }
            
            // Mettre à jour l'avatar si nécessaire
            if ($user->avatar !== $googleUser->avatar) {
                $updateData['avatar'] = $googleUser->avatar;
            }
            
            // Mettre à jour le nom complet si nécessaire
            if ($user->name !== $googleUser->name) {
                $fullName = $googleUser->name;
                $nameParts = explode(' ', trim($fullName), 2);
                $prenom = $nameParts[0] ?? '';
                $nom = $nameParts[1] ?? '';
                
                $updateData['name'] = $fullName;
                $updateData['nom'] = $nom;
                $updateData['prenom'] = $prenom;
            }
            
            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Connecter l'utilisateur
            Auth::login($user);

            // Générer un token Sanctum
            $token = $user->createToken('google-auth')->plainTextToken;

            // Rediriger vers le frontend avec les données de connexion
            $frontendUrl = 'http://localhost:3000/auth/google/success';
            $params = http_build_query([
                'token' => $token,
                'user' => json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ])
            ]);

            return redirect($frontendUrl . '?' . $params);

        } catch (\Exception $e) {
            // En cas d'erreur, rediriger vers le frontend avec un message d'erreur
            $frontendUrl = 'http://localhost:3000/auth/google/error';
            $params = http_build_query([
                'error' => 'Erreur lors de la connexion Google: ' . $e->getMessage()
            ]);

            return redirect($frontendUrl . '?' . $params);
        }
    }
}
