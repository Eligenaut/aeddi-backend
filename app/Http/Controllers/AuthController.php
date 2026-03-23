<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorizedEmail;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $user->load('meta');

        $token = $user->createToken('auth-token')->plainTextToken;

        $avatarUrl = $user->avatar ?? '';

        $permissions = [];
        if (!$user->isAdmin()) {
            $permissions = json_decode($user->getMeta('permissions'), true) ?? [];
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name                        ?? '',
                'nom'           => $user->getMeta('nom')              ?? '',
                'prenom'        => $user->getMeta('prenom')           ?? '',
                'email'         => $user->email                       ?? '',
                'avatar'        => $avatarUrl                         ?? '',
                'role'          => strtoupper($user->role ?? 'MEMBER'),
                'sub_role'      => json_decode($user->sub_role ?? '[]') ?? [],
                'permissions'   => $permissions,
                'etablissement' => $user->getMeta('etablissement')    ?? '',
                'parcours'      => $user->getMeta('parcours')         ?? '',
                'niveau'        => $user->getMeta('niveau')           ?? '',
                'promotion'     => $user->getMeta('promotion')        ?? '',
                'logement'      => $user->getMeta('logement')         ?? '',
                'bloc_campus'   => $user->getMeta('bloc_campus')      ?? '',
                'quartier'      => $user->getMeta('quartier')         ?? '',
                'telephone'     => $user->getMeta('telephone')        ?? '',
            ],
            'token' => $token,
        ]);
    }

    // Redirige vers Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback après authentification Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // ─── DEBUG TEMPORAIRE ───────────────────────────
            Log::info('Google user reçu', [
                'email'  => $googleUser->getEmail(),
                'name'   => $googleUser->getName(),
            ]);
            // ────────────────────────────────────────────────

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                Log::warning('Email non trouvé en base', ['email' => $googleUser->getEmail()]);
                return redirect(env('FRONTEND_URL') . '/login?error=email_non_autorise');
            }

            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }

            $user->load('meta');
            $token = $user->createToken('auth-token')->plainTextToken;

            $permissions = [];
            if (!$user->isAdmin()) {
                $permissions = json_decode($user->getMeta('permissions'), true) ?? [];
            }

            $userData = urlencode(json_encode([
                'id'            => $user->id,
                'name'          => $user->name,
                'nom'           => $user->getMeta('nom') ?? '',
                'prenom'        => $user->getMeta('prenom') ?? '',
                'email'         => $user->email,
                'avatar'        => $user->avatar ?? '',
                'role'          => strtoupper($user->role ?? 'MEMBER'),
                'sub_role'      => json_decode($user->sub_role ?? '[]') ?? [],
                'permissions'   => $permissions,
                'etablissement' => $user->getMeta('etablissement') ?? '',
            ]));

            return redirect(env('FRONTEND_URL') . '/auth/google/callback?token=' . $token . '&user=' . $userData);
        } catch (\Exception $e) {
            // ─── AFFICHE L'ERREUR EXACTE ─────────────────────
            Log::error('Erreur Google OAuth', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            // ─────────────────────────────────────────────────
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
        }
    }

    public function checkEmailAllowed(Request $request): JsonResponse
    {
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

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà inscrit. Veuillez vous connecter.'
            ], 409);
        }

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
            'image'         => 'nullable|string',
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

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé'
            ], 409);
        }

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
            DB::beginTransaction();

            $user = User::create([
                'name'     => trim($request->input('prenom') . ' ' . $request->input('nom')),
                'email'    => $email,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'MEMBER',
                'sub_role' => json_encode([]),
            ]);

            // Metas
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

            // ✅ Assigner les permissions MEMBER automatiquement
            $memberPermissions = RolePermission::findPermissions('MEMBER', null);
            if (!empty($memberPermissions)) {
                $user->setMeta('permissions', json_encode($memberPermissions));
            }

            // Image
            // Image → Cloudinary
            if ($request->has('image') && !empty($request->input('image'))) {
                $imageBase64 = $request->input('image');
                if (str_starts_with($imageBase64, 'data:image/')) {
                    try {
                        $imageParts = explode(',', $imageBase64);
                        if (count($imageParts) === 2) {
                            $imageData = base64_decode($imageParts[1], true);
                            if ($imageData === false) throw new \Exception('Décodage base64 échoué');

                            // Sauvegarde temporaire
                            $tmpFile = tempnam(sys_get_temp_dir(), 'profile_');
                            file_put_contents($tmpFile, $imageData);

                            \Cloudinary\Configuration\Configuration::instance([
                                'cloud' => [
                                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                                    'api_key'    => env('CLOUDINARY_API_KEY'),
                                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                                ],
                                'url' => ['secure' => true],
                            ]);

                            $api = new \Cloudinary\Api\Upload\UploadApi();
                            $result = $api->upload($tmpFile, [
                                'folder'    => 'aeddi/membres',
                                'public_id' => 'profile_' . $user->id,
                                'overwrite' => true,
                            ]);

                            unlink($tmpFile);

                            $user->update(['avatar' => $result['secure_url']]);

                            Log::info('Image profil uploadée sur Cloudinary', ['user_id' => $user->id]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erreur upload image Cloudinary: ' . $e->getMessage(), ['user_id' => $user->id]);
                    }
                }
            }

            // Token création mot de passe
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $webUrl = $request->input('url_frontend') . '/create-password?token=' . urlencode($token) . '&email=' . urlencode($email);
            $appUrl = 'aeddi://create-password?token=' . urlencode($token) . '&email=' . urlencode($email);

            try {
                Mail::send('emails.create_password', [
                    'webUrl' => $webUrl,
                    'appUrl' => $appUrl,
                    'user'   => $user
                ], function ($message) use ($user) {
                    $message->to($user->email)->subject('Créez votre mot de passe - AEDDI');
                });
                Log::info('Email création mot de passe envoyé', ['user_id' => $user->id, 'email' => $email]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email: ' . $e->getMessage(), ['user_id' => $user->id]);
            }

            // Cotisations
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

            Log::info('User registered successfully', ['user_id' => $user->id, 'email' => $email]);

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

        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

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

        $user->update([
            'password'          => Hash::make($request->input('password')),
            'email_verified_at' => now(),
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $user->load('meta');
        $apiToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe créé avec succès',
            'token'   => $apiToken,
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'nom'    => $user->getMeta('nom'),
                'prenom' => $user->getMeta('prenom'),
                'email'  => $user->email,
                'avatar' => $user->avatar ?? '',
                'role'   => strtoupper($user->role ?? 'MEMBER'),
            ]
        ], 200);
    }

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
        $user  = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        try {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $webUrl = $request->input('url_frontend') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);
            $appUrl = 'aeddi://reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);

            Mail::send('emails.password_reset', [
                'webUrl' => $webUrl,
                'appUrl' => $appUrl,
                'user'   => $user
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Réinitialisation de votre mot de passe - AEDDI');
            });

            Log::info('Email réinitialisation envoyé', ['user_id' => $user->id, 'email' => $email]);

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à ' . $email . '.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email reset: ' . $e->getMessage(), ['email' => $email]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

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

        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

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

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $user->load('meta');
        $apiToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
            'token'   => $apiToken,
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'nom'    => $user->getMeta('nom'),
                'prenom' => $user->getMeta('prenom'),
                'email'  => $user->email,
                'avatar' => $user->avatar ?? '',
                'role'   => strtoupper($user->role ?? 'MEMBER'),
            ]
        ], 200);
    }

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
