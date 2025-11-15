<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RecoveryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        // Debug: Log les données reçues
        \Log::info('Données reçues pour inscription:', $request->all());
        \Log::info('Headers: ' . json_encode($request->headers->all()));
        \Log::info('Content-Type: ' . $request->header('Content-Type'));
        \Log::info('Request method: ' . $request->method());
        
        // Gérer les données FormData
        $data = $request->all();
        
        // Si c'est FormData, les données peuvent être dans des champs différents
        if ($request->header('Content-Type') && str_contains($request->header('Content-Type'), 'multipart/form-data')) {
            \Log::info('Traitement FormData détecté');
            // Les données FormData sont déjà dans $request->all()
        }
        
        $validator = Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'email est autorisé et récupérer le recovery code
        $recoveryCode = RecoveryCode::where('email', $data['email'])
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$recoveryCode) {
            return response()->json([
                'success' => false,
                'message' => 'Email non autorisé ou code expiré. Contactez le trésorier de l\'AEDDI.'
            ], 403);
        }

        // Utiliser le recovery code existant comme code de vérification
        $verificationCode = $recoveryCode->code;

        // Envoyer l'email de vérification directement
        try {
            Mail::send('emails.verification', [
                'email' => $data['email'],
                'verificationCode' => $verificationCode
            ], function ($message) use ($data) {
                $message->to($data['email'])
                    ->subject('Vérification de votre compte AEDDI');
            });
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer l'inscription
            \Log::error('Erreur envoi email vérification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Un email de validation a été envoyé. Veuillez vérifier votre boîte mail.'
        ]);
    }

    /**
     * Vérifier le code de récupération
     */
    public function verifyRecoveryCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Code requis'
            ], 422);
        }

        $recoveryCode = RecoveryCode::where('code', $request->code)->first();

        if (!$recoveryCode) {
            return response()->json([
                'success' => true,
                'exists' => false,
                'used' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => true,
            'used' => $recoveryCode->used
        ]);
    }

    /**
     * Renvoyer le code de vérification
     */
    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;

        // Récupérer le recovery code existant
        $recoveryCode = RecoveryCode::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$recoveryCode) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun code valide trouvé pour cet email'
            ], 404);
        }

        // Utiliser le recovery code existant
        $verificationCode = $recoveryCode->code;

        // Envoyer l'email de vérification
        try {
            Mail::send('emails.verification', [
                'email' => $email,
                'verificationCode' => $verificationCode
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Code de vérification - AEDDI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Code de vérification renvoyé avec succès'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Vérifier l'email avec le code
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'verificationCode' => 'required|string|size:6',
            'registrationData' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides'
            ], 422);
        }

        // Debug: Log les données reçues
        \Log::info('Verify email request:', [
            'email' => $request->email,
            'verificationCode' => $request->verificationCode,
            'hasRegistrationData' => !empty($registrationData)
        ]);

        // Vérifier le code de récupération au lieu de pending_registrations
        $recoveryCode = RecoveryCode::where('email', $request->email)
            ->where('code', $request->verificationCode)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        // Debug: Log le recovery code trouvé
        \Log::info('Recovery code check:', [
            'found' => $recoveryCode ? true : false,
            'email' => $request->email,
            'code' => $request->verificationCode
        ]);

        if (!$recoveryCode) {
            return response()->json([
                'success' => false,
                'message' => 'Code de vérification invalide ou expiré'
            ], 400);
        }

        // Récupérer les données d'inscription depuis le localStorage (envoyées par le frontend)
        $registrationData = $request->registrationData ?? [];

        // Créer l'utilisateur avec les données du localStorage ou données minimales
        $userData = [
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)), // Mot de passe temporaire
            'role' => 'membre',
            'email_verified_at' => now()
        ];

        // Ajouter les données d'inscription si disponibles
        if (!empty($registrationData)) {
            $userData = array_merge($userData, [
                'prenom' => $registrationData['prenom'] ?? 'Utilisateur',
                'nom' => $registrationData['nom'] ?? 'Temporaire',
                'name' => ($registrationData['prenom'] ?? 'Utilisateur') . ' ' . ($registrationData['nom'] ?? 'Temporaire'),
                'etablissement' => $registrationData['etablissement'] ?? null,
                'parcours' => $registrationData['parcours'] ?? null,
                'niveau' => $registrationData['niveau'] ?? null,
                'promotion' => $registrationData['promotion'] ?? null,
                'logement' => $registrationData['logement'] ?? null,
                'bloc_campus' => $registrationData['blocCampus'] ?? null,
                'quartier' => $registrationData['quartier'] ?? null,
                'telephone' => $registrationData['telephone'] ?? null,
            ]);
        } else {
            // Données minimales si pas de registrationData
            $userData = array_merge($userData, [
                'prenom' => 'Utilisateur',
                'nom' => 'Temporaire',
                'name' => 'Utilisateur Temporaire',
            ]);
        }

        $user = User::create($userData);

        // Associer le nouvel utilisateur à toutes les cotisations existantes
        $cotisations = \App\Models\Cotisation::all();
        foreach ($cotisations as $cotisation) {
            \App\Models\CotisationMembre::firstOrCreate([
                'user_id' => $user->id,
                'cotisation_id' => $cotisation->id,
            ], [
                'statut' => 'non_paye',
                'montant_restant' => $cotisation->montant
            ]);
        }

        // Debug: Log les données d'inscription
        \Log::info('Registration data from localStorage:', [
            'image' => $registrationData['image'] ?? null,
            'image_type' => $registrationData['imageType'] ?? null,
            'image_name' => $registrationData['imageName'] ?? null
        ]);

        // Gérer l'image de profil selon le type
        $avatarUrl = null;
        $profileImagePath = null;
        
        // Si une image est fournie en base64
        if (isset($registrationData['image']) && str_starts_with($registrationData['image'], 'data:image/')) {
            $imageData = $registrationData['image'];
            $imageName = $registrationData['imageName'] ?? 'profile_' . $user->id . '.svg';
            
            // Extraire les données base64
            $imageData = explode(',', $imageData)[1];
            $imageData = base64_decode($imageData);
            
            // Déterminer l'extension selon le type
            $extension = 'svg';
            if (str_contains($registrationData['imageType'] ?? '', 'jpg') || str_contains($registrationData['imageType'] ?? '', 'jpeg')) {
                $extension = 'jpg';
            } elseif (str_contains($registrationData['imageType'] ?? '', 'png')) {
                $extension = 'png';
            }
            
            $imageName = 'profile_' . $user->id . '.' . $extension;
            $imagePath = 'profile_images/' . $imageName;
            $fullPath = storage_path('app/public/' . $imagePath);
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }
            
            file_put_contents($fullPath, $imageData);
            $profileImagePath = $imagePath;
            $avatarUrl = asset('storage/' . $imagePath);
        }
        // Pas d'image - utiliser les initiales par défaut
        else {
            $avatarUrl = null; // Pas d'avatar par défaut
            $profileImagePath = null;
        }
        
        // Mettre à jour les champs avatar et profile_image
        $user->update([
            'avatar' => $avatarUrl,
            'profile_image' => $profileImagePath
        ]);

        // Debug: Log les données finales de l'utilisateur
        \Log::info('User final data:', [
            'avatar' => $avatarUrl,
            'profile_image' => $profileImagePath,
            'user_id' => $user->id
        ]);

        // Marquer le recovery code comme utilisé
        $recoveryCode->update(['used' => true]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié et compte créé avec succès.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'avatar' => $user->avatar, // URL complète de l'avatar
                'profile_image' => $user->profile_image, // Chemin relatif
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email requis'
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé ou déjà vérifié'
            ], 404);
        }

        // Générer un nouveau code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(15)
        ]);

        // Envoyer l'email
        try {
            Mail::send('emails.verification', [
                'user' => $user,
                'verificationCode' => $verificationCode
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Vérification de votre compte AEDDI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Email de vérification renvoyé'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email vérification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Créer le mot de passe après vérification de l'email
     */
    public function createPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'confirmPassword' => 'required|string|min:8|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur a été créé via le nouveau flux (email_verified_at non null)
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non vérifié. Veuillez refaire l\'inscription avec le nouveau flux de vérification par email.'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Créer un token d'authentification
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe créé avec succès',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Tester l'accès aux images de profil
     */
    public function testProfileImage(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Logique de priorité des avatars
        $avatarUrl = $user->avatar;
        if (!$avatarUrl && $user->profile_image) {
            $avatarUrl = asset('storage/' . $user->profile_image);
        }

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'prenom' => $user->prenom,
            'nom' => $user->nom,
            'avatar_field' => $user->avatar,
            'profile_image_field' => $user->profile_image,
            'final_avatar_url' => $avatarUrl,
            'file_exists' => $user->profile_image ? file_exists(storage_path('app/public/' . $user->profile_image)) : false,
            'app_url' => config('app.url'),
            'storage_url' => config('filesystems.disks.public.url')
        ]);
    }

    /**
     * Demander un reset de mot de passe
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide ou inexistant',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Générer un code de récupération
        $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Créer ou mettre à jour le recovery code
        RecoveryCode::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $resetCode,
                'used' => false,
                'expires_at' => now()->addMinutes(15)
            ]
        );

        // Envoyer l'email de récupération
        try {
            Mail::send('emails.password-reset', [
                'resetCode' => $resetCode,
                'user' => $user
            ], function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Réinitialisation de votre mot de passe - AEDDI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse email.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email reset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier le code de récupération
        $recoveryCode = RecoveryCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$recoveryCode) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide ou expiré'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Marquer le code comme utilisé
        $recoveryCode->update(['used' => true]);

        // Générer un token Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'profile_image' => $user->profile_image,
                'role' => $user->role ?? 'member',
                'etablissement' => $user->etablissement,
                'parcours' => $user->parcours,
                'niveau' => $user->niveau,
                'promotion' => $user->promotion,
                'logement' => $user->logement,
                'bloc_campus' => $user->bloc_campus,
                'quartier' => $user->quartier,
                'telephone' => $user->telephone,
            ]
        ]);
    }
}
