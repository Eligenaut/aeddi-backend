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
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Vérifier si un email est autorisé (appelé avant l'inscription)
     */
    public function checkEmailAllowed(Request $request): JsonResponse
    {
        // Validation stricte
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format d\'email invalide',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        // Vérifier si déjà inscrit
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà inscrit. Veuillez vous connecter.'
            ], 409);
        }

        // Vérifier si autorisé
        $authorized = AuthorizedEmail::where('meta_key', 'email')
            ->where('meta_value', $email)
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
        ], 200);
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|max:255',
            'nom'           => 'required|string|max:100',
            'prenom'        => 'required|string|max:100',
            'etablissement' => 'required|string|max:255',
            'parcours'      => 'required|string|max:255',
            'niveau'        => 'required|string|max:50',
            'promotion'     => 'required|string|max:50',
            'logement'      => 'required|string|max:50',
            'telephone'     => 'required|string|max:20',
            'url_frontend'  => 'required|url',
            'image'         => 'nullable|string', // base64
            'imageName'     => 'nullable|string',
            'imageType'     => 'nullable|string',
            'blocCampus'    => 'nullable|string|max:100',
            'quartier'      => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        // 1. Vérifier si l'email existe déjà
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé'
            ], 409);
        }

        // 2. Vérifier si l'email est autorisé
        $authorized = AuthorizedEmail::where('meta_key', 'email')
            ->where('meta_value', $email)
            ->first();

        if (!$authorized) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email n\'est pas autorisé à s\'inscrire'
            ], 403);
        }

        try {
            // 3. Créer l'utilisateur avec une transaction
            DB::beginTransaction();

            $user = User::create([
                'name'     => trim($request->input('prenom') . ' ' . $request->input('nom')),
                'email'    => $email,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'membre',
            ]);

            // 4. Stocker les données secondaires
            $metas = [
                'nom'           => $request->input('nom'),
                'prenom'        => $request->input('prenom'),
                'etablissement' => $request->input('etablissement'),
                'parcours'      => $request->input('parcours'),
                'niveau'        => $request->input('niveau'),
                'promotion'     => $request->input('promotion'),
                'logement'      => $request->input('logement'),
                'bloc_campus'   => $request->input('blocCampus'),
                'quartier'      => $request->input('quartier'),
                'telephone'     => $request->input('telephone'),
            ];

            foreach ($metas as $key => $value) {
                if ($value !== null && $value !== '') {
                    $user->setMeta($key, $value);
                }
            }

            // 5. Gérer l'image de profil
            if ($request->has('image') && !empty($request->input('image'))) {
                $imageBase64 = $request->input('image');

                // Vérifier que c'est bien un data URL
                if (str_starts_with($imageBase64, 'data:image/')) {
                    try {
                        $imageParts = explode(',', $imageBase64);
                        if (count($imageParts) === 2) {
                            $imageData = base64_decode($imageParts[1], true);

                            if ($imageData === false) {
                                throw new \Exception('Décodage base64 échoué');
                            }

                            $extension = 'jpg';
                            $imageType = $request->input('imageType', '');

                            if (str_contains($imageType, 'png')) {
                                $extension = 'png';
                            } elseif (str_contains($imageType, 'webp')) {
                                $extension = 'webp';
                            }

                            $imageName = 'profile_' . $user->id . '.' . $extension;
                            $imagePath = 'profile_images/' . $imageName;
                            $fullPath  = storage_path('app/public/' . $imagePath);

                            // Créer le répertoire s'il n'existe pas
                            $directory = dirname($fullPath);
                            if (!file_exists($directory)) {
                                mkdir($directory, 0755, true);
                            }

                            // Écrire le fichier
                            file_put_contents($fullPath, $imageData);

                            // Mettre à jour l'avatar
                            $user->update(['avatar' => asset('storage/' . $imagePath)]);
                            $user->setMeta('profile_image', $imagePath);

                            Log::info('Image profil créée', ['user_id' => $user->id, 'path' => $imagePath]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erreur traitement image: ' . $e->getMessage(), ['user_id' => $user->id]);
                        // Ne pas bloquer l'inscription si l'image échoue
                    }
                }
            }

            // 6. Générer un token de création de mot de passe
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // 7. Préparer l'URL de réinitialisation
            $resetUrl = $request->input('url_frontend') . '/create-password?token=' . urlencode($token) . '&email=' . urlencode($email);

            // 8. Envoyer l'email
            try {
                Mail::send('emails.create_password', [
                    'resetUrl' => $resetUrl,
                    'user'     => $user
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Créez votre mot de passe - AEDDI');
                });
                Log::info('Email création mot de passe envoyé', ['user_id' => $user->id, 'email' => $email]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email création mot de passe: ' . $e->getMessage(), ['user_id' => $user->id]);
                // Ne pas bloquer l'inscription si l'email échoue
            }

            // 9. Associer aux cotisations existantes
            try {
                $cotisations = \App\Models\Cotisation::all();
                foreach ($cotisations as $cotisation) {
                    \App\Models\CotisationMembre::firstOrCreate(
                        ['user_id' => $user->id, 'cotisation_id' => $cotisation->id],
                        ['statut' => 'non_paye', 'montant_restant' => $cotisation->montant]
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Erreur association cotisations: ' . $e->getMessage(), ['user_id' => $user->id]);
            }

            DB::commit();

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email'   => $email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie ! Un email de création de mot de passe a été envoyé.',
                'user_id' => $user->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'inscription: ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription'
            ], 500);
        }
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
            'confirmPassword' => 'required|string|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');

        // Vérifier le token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 401);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password'          => Hash::make($request->input('password')),
            'email_verified_at' => now(),
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $apiToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe créé avec succès',
            'token'   => $apiToken,
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
        ], 200);
    }


    /**
     * Demander un reset de mot de passe
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email|exists:users,email',
            'url_frontend' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide ou inexistant',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Vérifier que l'utilisateur existe
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        try {
            // Générer un token
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Préparer l'URL
            $resetUrl = $request->input('url_frontend') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);

            // Envoyer l'email
            Mail::send('emails.password_reset', [
                'resetUrl' => $resetUrl,
                'user'     => $user
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Réinitialisation de votre mot de passe - AEDDI');
            });

            Log::info('Email réinitialisation envoyé', [
                'user_id' => $user->id,
                'email'   => $email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à ' . $email . '.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email reset: ' . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);

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

        $email = $request->input('email');
        $token = $request->input('token');

        // Vérifier le token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 401);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $apiToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
            'token'   => $apiToken,
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
        ], 200);
    }

    /**
     * Ajouter un email autorisé
     */
    public function addAuthorizedEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        // Vérifier si déjà dans la liste
        $existing = AuthorizedEmail::where('meta_key', 'email')
            ->where('meta_value', $email)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà dans la liste'
            ], 409);
        }

        try {
            AuthorizedEmail::create([
                'user_id'    => Auth::id(),
                'meta_key'   => 'email',
                'meta_value' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email autorisé ajouté avec succès'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur ajout email autorisé: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'email'
            ], 500);
        }
    }

    /**
     * Lister les emails autorisés
     */
    public function getAuthorizedEmails(): JsonResponse
    {
        try {
            $emails = AuthorizedEmail::where('meta_key', 'email')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id'    => $item->id,
                    'email' => $item->meta_value,
                ]);

            return response()->json([
                'success' => true,
                'data'    => $emails
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lecture emails autorisés: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des emails'
            ], 500);
        }
    }

    /**
     * Supprimer un email autorisé
     */
    public function deleteAuthorizedEmail($id): JsonResponse
    {
        try {
            $authorized = AuthorizedEmail::find($id);

            if (!$authorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email non trouvé'
                ], 404);
            }

            $authorized->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur suppression email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}
