<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorizedEmail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email',
            'nom'           => 'required|string',
            'prenom'        => 'required|string',
            'etablissement' => 'required|string',
            'parcours'      => 'required|string',
            'niveau'        => 'required|string',
            'promotion'     => 'required|string',
            'logement'      => 'required|string',
            'telephone'     => 'required|string',
            'url_frontend'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 1. Vérifier si l'email existe déjà dans users
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé'
            ], 400);
        }

        // 2. Vérifier si l'email est dans la liste autorisée
        $authorized = AuthorizedEmail::where('meta_key', 'email')
            ->where('meta_value', $request->email)
            ->first();

        if (!$authorized) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email n\'est pas autorisé à s\'inscrire'
            ], 403);
        }

        // 3. Créer l'utilisateur
        $user = User::create([
            'name'     => $request->prenom . ' ' . $request->nom,
            'email'    => $request->email,
            'password' => Hash::make(Str::random(32)),
            'role'     => 'membre',
        ]);

        // 4. Stocker les données secondaires dans user_meta
        $metas = [
            'nom'           => $request->nom,
            'prenom'        => $request->prenom,
            'etablissement' => $request->etablissement,
            'parcours'      => $request->parcours,
            'niveau'        => $request->niveau,
            'promotion'     => $request->promotion,
            'logement'      => $request->logement,
            'bloc_campus'   => $request->blocCampus ?? null,
            'quartier'      => $request->quartier ?? null,
            'telephone'     => $request->telephone,
        ];

        foreach ($metas as $key => $value) {
            if ($value !== null) {
                $user->setMeta($key, $value);
            }
        }

        // 5. Gérer l'image de profil
        if ($request->image && str_starts_with($request->image, 'data:image/')) {
            $imageData = explode(',', $request->image)[1];
            $imageData = base64_decode($imageData);

            $extension = 'jpg';
            if (str_contains($request->imageType ?? '', 'png')) {
                $extension = 'png';
            }

            $imageName = 'profile_' . $user->id . '.' . $extension;
            $imagePath = 'profile_images/' . $imageName;
            $fullPath  = storage_path('app/public/' . $imagePath);

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            file_put_contents($fullPath, $imageData);
            $user->update(['avatar' => asset('storage/' . $imagePath)]);
            $user->setMeta('profile_image', $imagePath);
        }

        // 6. Générer un token de création de mot de passe
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // 7. Envoyer le mail avec le lien de création de mot de passe
        $resetUrl = $request->url_frontend . '/create-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            Mail::send('emails.create_password', [
                'resetUrl' => $resetUrl,
                'user'     => $user
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Créez votre mot de passe - AEDDI');
            });
        } catch (\Exception $e) {
            Log::error('Erreur envoi email création mot de passe: ' . $e->getMessage());
        }

        // 8. Associer aux cotisations existantes
        $cotisations = \App\Models\Cotisation::all();
        foreach ($cotisations as $cotisation) {
            \App\Models\CotisationMembre::firstOrCreate(
                ['user_id' => $user->id, 'cotisation_id' => $cotisation->id],
                ['statut' => 'non_paye', 'montant_restant' => $cotisation->montant]
            );
        }

        Log::info('User registered successfully', [
            'user_id' => $user->id,
            'email'   => $user->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie ! Un email de création de mot de passe a été envoyé.'
        ]);
    }

    /**
     * Créer le mot de passe après inscription
     */
    public function createPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'           => 'required|email',
            'token'           => 'required|string',
            'password'        => 'required|string|min:8',
            'confirmPassword' => 'required|string|min:8|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Vérifier le token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password'           => Hash::make($request->password),
            'email_verified_at'  => now(),
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe créé avec succès',
            'token'   => $token,
            'user'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'nom'           => $user->getMeta('nom'),
                'prenom'        => $user->getMeta('prenom'),
                'email'         => $user->email,
                'avatar'        => $user->avatar,
                'profile_image' => $user->getMeta('profile_image'),
                'role'          => $user->role,
            ]
        ]);
    }

    /**
     * Demander un reset de mot de passe
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email|exists:users,email',
            'url_frontend' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide ou inexistant',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Générer un token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = $request->url_frontend . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            Mail::send('emails.password_reset', [
                'resetUrl' => $resetUrl,
                'user'     => $user
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Réinitialisation de votre mot de passe - AEDDI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email reset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Vérifier le token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
            'token'   => $token,
            'user'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'nom'           => $user->getMeta('nom'),
                'prenom'        => $user->getMeta('prenom'),
                'email'         => $user->email,
                'avatar'        => $user->avatar,
                'profile_image' => $user->getMeta('profile_image'),
                'role'          => $user->role,
            ]
        ]);
    }

    /**
     * Vérifier si un email est autorisé
     */
    public function checkEmailAllowed(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Email invalide'
        ], 422);
    }

    // Vérifier si déjà inscrit
    $existingUser = User::where('email', $request->email)->first();
    if ($existingUser) {
        return response()->json([
            'success' => false,
            'message' => 'Cet email est déjà inscrit. Veuillez vous connecter.'
        ], 400);
    }

    // Vérifier si autorisé
    $authorized = AuthorizedEmail::where('meta_key', 'email')
        ->where('meta_value', $request->email)
        ->first();

    if (!$authorized) {
        return response()->json([
            'success' => false,
            'message' => 'Cet email n\'est pas dans notre liste d\'autorisation. Contactez le trésorier de l\'AEDDI.'
        ], 403);
    }

    return response()->json([
        'success' => true,
        'message' => 'Email autorisé !'
    ]);
}
}