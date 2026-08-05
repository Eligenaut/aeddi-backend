<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Permissions;
use App\Models\UserLogement;
use App\Models\AuthorizedEmail;
use App\Models\Etablissement;
use App\Models\Parcours;
use App\Models\Niveau;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ─── Helper : envoie un email via Laravel Mail (SMTP Brevo) ──────────────
    private function sendEmailViaMail(string $toEmail, string $toName, string $subject, string $htmlContent): void
    {
        Mail::html($htmlContent, function ($message) use ($toEmail, $toName, $subject) {
            $message->to($toEmail, $toName)
                ->subject($subject);
        });
    }

    // ─── Helper : cookie httpOnly contenant le token Sanctum ──────────────────
    private function authCookie(string $token, int $minutes = 60 * 24 * 7)
    {
        return cookie(
            'auth_token',
            $token,
            $minutes,
            '/',
            null,
            env('APP_ENV') === 'production',
            true,
            false,
            'Lax'
        );
    }

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

        $permissions = Permissions::resolve($user);

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
        ])->withCookie($this->authCookie($token));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $user->load('meta');

        $avatarUrl = $user->avatar ?? '';

        $permissions = Permissions::resolve($user);

        $rawEtablissement = $user->getMeta('etablissement') ?? '';
        $rawParcours = $user->getMeta('parcours') ?? '';
        $rawNiveau = $user->getMeta('niveau') ?? '';
        $rawPromotion = $user->getMeta('promotion') ?? '';

        $etablissement = $rawEtablissement;
        if (is_numeric($rawEtablissement)) {
            $etab = Etablissement::find((int)$rawEtablissement);
            $etablissement = $etab?->nom ?? $rawEtablissement;
        }

        $parcours = $rawParcours;
        if (is_numeric($rawParcours)) {
            $p = Parcours::find((int)$rawParcours);
            $parcours = $p?->nom ?? $rawParcours;
        }

        $niveau = $rawNiveau;
        if (is_numeric($rawNiveau)) {
            $n = Niveau::find((int)$rawNiveau);
            $niveau = $n?->nom ?? $rawNiveau;
        }

        $promotion = $rawPromotion;
        if (is_numeric($rawPromotion)) {
            $prom = Promotion::find((int)$rawPromotion);
            $promotion = $prom?->nom ?? $rawPromotion;
        }

        // Logement : d'abord les metas, puis UserLogement si vide
        $logement       = $user->getMeta('logement')    ?? '';
        $blocCampus     = $user->getMeta('bloc_campus') ?? '';
        $quartier       = $user->getMeta('quartier')    ?? '';
        $optionCampus   = '';
        $sectionCampus  = '';

        if (empty($logement) && empty($blocCampus) && empty($quartier)) {
            $userLogement = \App\Models\UserLogement::where('user_id', $user->id)->first();
            if ($userLogement) {
                $typeLog = \App\Models\TypeLogement::find($userLogement->type_logement_id);
                if ($typeLog) {
                    $logement = strtolower($typeLog->nom) === 'ville' ? 'ville' : 'campus';
                }
                if ($userLogement->option_campus_id) {
                    $opt = \App\Models\OptionCampus::find($userLogement->option_campus_id);
                    $optionCampus = $opt?->nom ?? '';
                }
                if ($userLogement->section_campus_id) {
                    $sec = \App\Models\SectionCampus::find($userLogement->section_campus_id);
                    $sectionCampus = $sec?->nom ?? '';
                }
                if ($userLogement->bloc_campus_id) {
                    $bloc = \App\Models\BlocCampus::find($userLogement->bloc_campus_id);
                    $blocCampus = $bloc?->nom ?? '';
                }
                if ($userLogement->quartier_id) {
                    $q = \App\Models\Quartier::find($userLogement->quartier_id);
                    $quartier = $q?->nom ?? '';
                }
            }
        }

        return response()->json([
            'success' => true,
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
                'etablissement' => $etablissement,
                'parcours'      => $parcours,
                'niveau'        => $niveau,
                'promotion'     => $promotion,
                'logement'       => $logement,
                'bloc_campus'    => $blocCampus,
                'option_campus'  => $optionCampus,
                'section_campus' => $sectionCampus,
                'quartier'       => $quartier,
                'telephone'     => $user->getMeta('telephone')        ?? '',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ])->withCookie(cookie(
            'auth_token',
            '',
            -1,
            '/',
            null,
            false,
            true,
            false,
            'Lax'
        ));
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'nom'           => 'required|string|max:255',
            'prenom'        => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'telephone'     => 'nullable|string|max:20',
            'etablissement' => 'nullable|string|max:255',
            'parcours'      => 'nullable|string|max:255',
            'niveau'        => 'nullable|string|max:255',
            'promotion'     => 'nullable|string|max:255',
            'logement'      => 'nullable|string|max:50',
            'blocCampus'    => 'nullable|string|max:255',
            'quartier'      => 'nullable|string|max:255',
            'image'         => 'nullable|string',
        ]);

        try {
            $user->update([
                'name'  => trim($validated['prenom'] . ' ' . $validated['nom']),
                'email' => $validated['email'],
            ]);

            $metas = [
                'nom'           => $validated['nom'],
                'prenom'        => $validated['prenom'],
                'telephone'     => $validated['telephone']     ?? '',
                'etablissement' => $validated['etablissement'] ?? '',
                'parcours'      => $validated['parcours']      ?? '',
                'niveau'        => $validated['niveau']        ?? '',
                'promotion'     => $validated['promotion']     ?? '',
                'logement'      => $validated['logement']      ?? '',
                'bloc_campus'   => isset($validated['logement']) && $validated['logement'] === 'campus' ? ($validated['blocCampus'] ?? '') : '',
                'quartier'      => $validated['quartier']      ?? '',
            ];

            foreach ($metas as $key => $value) {
                $user->setMeta($key, $value);
            }

            if (!empty($validated['image']) && str_starts_with($validated['image'], 'data:image/')) {
                $imageParts = explode(',', $validated['image']);
                if (count($imageParts) === 2) {
                    $imageData = base64_decode($imageParts[1], true);
                    $publicId  = 'profile_' . $user->id;
                    $avatarUrl = $this->uploadImageToCloudinary($imageData, $publicId);
                    $user->update(['avatar' => $avatarUrl]);
                }
            }

            $user->load('meta');

            $avatarUrl = $user->avatar ?? '';
            $permissions = Permissions::resolve($user);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data'    => [
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
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function googleMobile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_token' => 'required|string',
                'email'    => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Données invalides'], 422);
            }

            $client = new \Google\Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json(['success' => false, 'message' => 'Token Google invalide'], 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Email non autorisé'], 403);
            }

            if (empty($user->avatar) && $request->avatar) {
                $user->update(['avatar' => $request->avatar]);
            }

            $user->load('meta');
            $token = $user->createToken('auth-token')->plainTextToken;

            $permissions = Permissions::resolve($user);

            return response()->json([
                'success' => true,
                'token'   => $token,
                'user'    => [
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
                ],
            ])->withCookie($this->authCookie($token));
        } catch (\Exception $e) {
            Log::error('Erreur Google Mobile:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            Log::info('Google user OK', ['email' => $googleUser->getEmail()]);

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                return redirect(env('FRONTEND_URL') . '/login?error=email_non_autorise');
            }

            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }

            $user->load('meta');
            $token = $user->createToken('auth-token')->plainTextToken;

            $permissions = Permissions::resolve($user);

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

            return redirect(env('FRONTEND_URL') . '/auth/google/success?user=' . $userData)
                ->withCookie($this->authCookie($token));
        } catch (\Exception $e) {
            Log::error('Erreur Google OAuth', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return redirect(env('FRONTEND_URL') . '/login?error=' . urlencode($e->getMessage()));
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

        $authorized = AuthorizedEmail::where('email', $email)->first();

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
            'email'           => 'required|email|max:255',
            'nom'             => 'required|string|max:100',
            'prenom'          => 'required|string|max:100',
            'etablissement'   => 'required|string|max:255',
            'parcours'        => 'required|string|max:255',
            'niveau'          => 'required|string|max:50',
            'promotion'       => 'required|string|max:50',
            'telephone'       => 'required|string|max:20',
            'url_frontend'    => 'required|url',
            'image'           => 'nullable|string',
            'imageName'       => 'nullable|string',
            'imageType'       => 'nullable|string',
            'type_logement'   => 'nullable|string|max:50',
            'option_campus'   => 'nullable|string|max:255',
            'section_campus'  => 'nullable|string|max:255',
            'bloc_campus'     => 'nullable|string|max:255',
            'quartier'        => 'nullable|string|max:255',
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

        $authorized = AuthorizedEmail::where('email', $email)->first();

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
                'role'     => $authorized->role,
                'sub_role' => json_encode([]),
            ]);

            $metas = [
                'nom'           => $request->input('nom'),
                'prenom'        => $request->input('prenom'),
                'etablissement' => $request->input('etablissement'),
                'parcours'      => $request->input('parcours'),
                'niveau'        => $request->input('niveau'),
                'promotion'     => $request->input('promotion'),
                'telephone'     => $request->input('telephone'),
            ];

            foreach ($metas as $key => $value) {
                if ($value !== null && $value !== '') {
                    $user->setMeta($key, $value);
                }
            }

            if ($request->filled('type_logement') && $request->filled('option_campus')) {
                UserLogement::create([
                    'user_id'          => $user->id,
                    'type_logement_id' => $request->input('type_logement'),
                    'option_campus_id' => $request->input('option_campus'),
                    'section_campus_id' => $request->input('section_campus'),
                    'bloc_campus_id'   => $request->input('bloc_campus'),
                ]);
            }

            if ($request->filled('quartier')) {
                $typeVille = \App\Models\TypeLogement::where('nom', 'Ville')->orWhere('nom', 'ville')->first();
                UserLogement::create([
                    'user_id'          => $user->id,
                    'type_logement_id' => $typeVille?->id,
                    'quartier_id'      => $request->input('quartier'),
                ]);
            }

            // Lier l'email autorisé au compte créé + aligner le rôle (miroir)
            AuthorizedEmail::where('email', $email)->update([
                'user_id' => $user->id,
                'role'    => $user->role,
            ]);

            if ($request->has('image') && !empty($request->input('image'))) {
                $imageBase64 = $request->input('image');
                if (str_starts_with($imageBase64, 'data:image/')) {
                    try {
                        $imageParts = explode(',', $imageBase64);
                        if (count($imageParts) === 2) {
                            $imageData = base64_decode($imageParts[1], true);
                            if ($imageData === false) throw new \Exception('Décodage base64 échoué');

                            $user->update([
                                'avatar' => \App\Helpers\ImageStorage::storeFromBase64($imageData, 'membres'),
                            ]);
                            Log::info('Image profil uploadée localement', ['user_id' => $user->id]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Erreur upload image profil: ' . $e->getMessage(), ['user_id' => $user->id]);
                    }
                }
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                $htmlContent = view('emails.create_password', [
                    'code' => $code,
                    'user'   => $user
                ])->render();

                $this->sendEmailViaMail(
                    $user->email,
                    $user->name,
                    'Créez votre mot de passe - AEDDI',
                    $htmlContent
                );

                Log::info('Email création mot de passe envoyé', ['user_id' => $user->id, 'email' => $email]);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi email: ' . $e->getMessage(), ['user_id' => $user->id]);
            }

            try {
                $cotisations = \App\Models\Cotisation::all();
                foreach ($cotisations as $cotisation) {
                    if ($user->role === 'NOVICE') {
                        $montant = $cotisation->montant_novice;
                    } else {
                        $montant = $cotisation->montant_ancien;
                    }

                    \App\Models\CotisationMembre::firstOrCreate(
                        ['user_id' => $user->id, 'cotisation_id' => $cotisation->id],
                        [
                            'statut'          => 'non_paye',
                            'montant_restant' => $montant,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Erreur association cotisations: ' . $e->getMessage(), ['user_id' => $user->id]);
            }

            DB::commit();

            Log::info('User registered successfully', ['user_id' => $user->id, 'email' => $email]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie ! Un email de création de mot de passe a été envoyé.',
                'user_id' => $user->id
            ], 201);
        } catch (\Throwable $e) {
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

    public function verifyCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $code  = $request->input('code');

        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetRecord || !Hash::check($code, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide ou expiré'
            ], 401);
        }

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->update(['verified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié avec succès',
        ], 200);
    }

    public function checkVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
            ], 422);
        }

        $email = $request->input('email');
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        return response()->json([
            'success'  => true,
            'verified' => $record && $record->verified,
        ], 200);
    }

    public function resendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
            ], 422);
        }

        $email = $request->input('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune inscription trouvée pour cet email'
            ], 404);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'     => Hash::make($code),
                'verified'  => false,
                'created_at' => now(),
            ]
        );

        try {
            $htmlContent = view('emails.create_password', [
                'code' => $code,
                'user' => $user
            ])->render();

            $this->sendEmailViaMail(
                $user->email,
                $user->name,
                'Créez votre mot de passe - AEDDI',
                $htmlContent
            );

            Log::info('Nouveau code envoyé', ['user_id' => $user->id, 'email' => $email]);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi nouveau code: ' . $e->getMessage(), ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nouveau code envoyé par email',
        ], 200);
    }

    public function createPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'           => 'required|email',
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

        $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$resetRecord || !$resetRecord->verified) {
            return response()->json([
                'success' => false,
                'message' => 'Code non vérifié. Veuillez d\'abord vérifier votre code.'
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
        ], 200)->withCookie($this->authCookie($apiToken));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
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
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'     => Hash::make($code),
                    'verified'  => false,
                    'created_at' => now(),
                ]
            );

            $htmlContent = view('emails.password_reset', [
                'code' => $code,
                'user' => $user
            ])->render();

            $this->sendEmailViaMail(
                $user->email,
                $user->name,
                'Réinitialisation de votre mot de passe - AEDDI',
                $htmlContent
            );

            Log::info('Email réinitialisation envoyé', ['user_id' => $user->id, 'email' => $email]);

            return response()->json([
                'success' => true,
                'message' => 'Un code de réinitialisation a été envoyé à ' . $email . '.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi email reset: ' . $e->getMessage(), ['email' => $email]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
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
        ], 200)->withCookie($this->authCookie($apiToken));
    }

    public function addAuthorizedEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'role'  => 'required|in:NOVICE,MEMBER'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        $existing = AuthorizedEmail::where('email', $request->input('email'))->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà dans la liste'
            ], 409);
        }

        try {
            AuthorizedEmail::create([
                'user_id' => null,
                'email'   => $request->input('email'),
                'role'    => $request->input('role', 'NOVICE'),
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
            $items = AuthorizedEmail::orderBy('created_at', 'desc')->get();

            // Charger en une requête les comptes correspondants (évite le N+1)
            $users = User::whereIn('email', $items->pluck('email'))
                ->get()
                ->keyBy(fn ($u) => strtolower($u->email));

            $emails = $items->map(function ($item) use ($users) {
                $user = $users->get(strtolower($item->email));

                if ($user) {
                    $subRole = json_decode($user->sub_role ?? '[]', true);
                    return [
                        'id'               => $item->id,
                        'email'            => $user->email,
                        'role'             => strtoupper($user->role ?? 'NOVICE'),
                        'sub_role'         => is_array($subRole) ? array_values($subRole) : [],
                        'user_id'          => $user->id,
                        'registered'       => true,
                        'email_verified_at'=> $user->email_verified_at?->toISOString(),
                        'verified'         => $user->email_verified_at !== null,
                        'created_at'       => $item->created_at,
                    ];
                }

                return [
                    'id'               => $item->id,
                    'email'            => $item->email,
                    'role'             => strtoupper($item->role ?? 'NOVICE'),
                    'sub_role'         => [],
                    'user_id'          => null,
                    'registered'       => false,
                    'email_verified_at'=> null,
                    'verified'         => false,
                    'created_at'       => $item->created_at,
                ];
            });

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

    private function uploadImageToCloudinary(string $imageData, string $publicId): string
    {
        return \App\Helpers\ImageStorage::storeFromBase64($imageData, 'membres');
    }
}
