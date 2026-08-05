<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorizedEmail;
use App\Models\Cotisation;
use App\Models\CotisationMembre;
use App\Models\UserNotification;
use App\Models\Activite;
use App\Models\Etablissement;
use App\Models\Parcours;
use App\Models\Niveau;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    private function getSubRoleLabel(?string $subRole): string
    {
        if (!$subRole) return '';

        $labels = [
            'president' => 'Président',
            'vice_president' => 'Vice Président',
            'tresorier' => 'Trésorier(e)',
            'vice_tresorier' => 'Vice Trésorier',
            'commissaire_compte' => 'Commissaire au compte',
            'commission_cercle_etude' => 'Commission (Cercle d\'étude)',
            'commission_informatique' => 'Commission (Informatique)',
            'commission_logement' => 'Commission (Logement)',
            'commission_social' => 'Commission (Social)',
            'commission_fete' => 'Commission (Fête)',
            'commission_sport' => 'Commission (Sport)',
            'commission_communication' => 'Commission (Communication)',
            'commission_environnement' => 'Commission (Environnement)',
        ];

        return $labels[$subRole] ?? $subRole;
    }

    // ─── Recalibrer les montants de cotisation selon le rôle ──
    private function recalibrateCotisationAmounts(User $member, string $role): void
    {
        $rows = CotisationMembre::where('user_id', $member->id)->get();

        foreach ($rows as $cm) {
            // Une cotisation déjà payée ne change pas
            if ($cm->statut === 'paye') continue;

            $cotisation = $cm->cotisation;
            if (!$cotisation) continue;

            $montant = $role === 'NOVICE'
                ? $cotisation->montant_novice
                : $cotisation->montant_ancien;

            if ($montant === null) continue;

            $cm->update(['montant_restant' => (float) $montant]);
        }
    }

    private function notifyAllMembers(string $type, array $payload, ?int $excludeUserId = null): void
    {
        $recipients = User::query()
            ->where('role', '!=', 'ADMIN')
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->pluck('id');

        if ($recipients->isEmpty()) return;

        $rows = $recipients->map(fn ($uid) => [
            'user_id' => $uid,
            'type' => $type,
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        UserNotification::insert($rows);
    }
    // ─── Helper upload image locale (WebP) ────────────────────
    private function uploadAvatarLocal(string $imageData, string $publicId): string
    {
        return \App\Helpers\ImageStorage::storeFromBase64($imageData, 'membres');
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

        $rawEtablissement = $member->getMeta('etablissement') ?? '';
        $rawParcours = $member->getMeta('parcours') ?? '';
        $rawNiveau = $member->getMeta('niveau') ?? '';
        $rawPromotion = $member->getMeta('promotion') ?? '';

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

        $logement       = $member->getMeta('logement')    ?? '';
        $blocCampus     = $member->getMeta('bloc_campus') ?? '';
        $quartier       = $member->getMeta('quartier')    ?? '';
        $optionCampus   = '';
        $sectionCampus  = '';

        if (empty($logement) && empty($blocCampus) && empty($quartier)) {
            $userLogement = \App\Models\UserLogement::where('user_id', $member->id)->first();
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

        return [
            'id'               => $member->id,
            'name'             => $member->name ?? '',
            'nom'              => $member->getMeta('nom') ?? '',
            'prenom'           => $member->getMeta('prenom') ?? '',
            'email'            => $member->email ?? '',
            'avatar'           => $this->getAvatarUrl($member),
            'role'             => strtoupper($member->role ?? 'MEMBER'),
            'sub_role'         => json_decode($member->sub_role ?? '[]') ?? [],
            'etablissement'    => $etablissement,
            'parcours'         => $parcours,
            'niveau'           => $niveau,
            'promotion'        => $promotion,
            'logement'         => $logement,
            'bloc_campus'      => $blocCampus,
            'option_campus'    => $optionCampus,
            'section_campus'   => $sectionCampus,
            'quartier'         => $quartier,
            'telephone'        => $member->getMeta('telephone') ?? '',
            'statut'           => $member->email_verified_at ? 'actif' : 'en_attente',
            'cotisation_stats' => $cotisationStats,
            'created_at'       => $member->created_at->toDateTimeString(),
            'updated_at'       => $member->updated_at->toDateTimeString(),
        ];
    }

    // ─── Liste tous les membres ─────────────────────────────
    // ─── Statistiques du tableau de bord ──────────────────────
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user('sanctum');

            $bureau  = User::where('role', 'BUREAU')->count();
            $membres = User::where('role', 'MEMBER')->count();
            $total   = User::where('role', '!=', 'ADMIN')->count();

            $totalActivites     = Activite::count();
            $activitesEnCours   = Activite::where('statut', 'en_cours')->count();
            $activitesTerminees = Activite::where('statut', 'terminee')->count();

            // ═══ GESTION (ADMIN / BUREAU) : agrégats globaux ═══
            if ($user && in_array(strtoupper($user->role), ['ADMIN', 'BUREAU'])) {
                $totalCotisations = CotisationMembre::count();
                $totalPaye        = CotisationMembre::where('statut', 'paye')->count();
                $totalNonPaye     = CotisationMembre::whereIn('statut', ['non_paye', 'reste'])->count();
                $montantRestant   = (float) CotisationMembre::whereIn('statut', ['non_paye', 'reste'])
                    ->sum('montant_restant');

                $cotisations = [
                    'total_cotisations' => $totalCotisations,
                    'total_paye'        => $totalPaye,
                    'total_non_paye'    => $totalNonPaye,
                    'montant_restant'   => $montantRestant,
                ];
            } else {
                // ═══ MEMBRE (NOVICE / MEMBER) : uniquement ses propres cotisations ═══
                $cotisationsMembre = $user
                    ? CotisationMembre::with('cotisation')->where('user_id', $user->id)->get()
                    : collect();

                $montantTotal = $cotisationsMembre->sum(function ($cm) use ($user) {
                    if (!$cm->cotisation) return 0;
                    return $user->role === 'NOVICE'
                        ? $cm->cotisation->montant_novice
                        : $cm->cotisation->montant_ancien;
                });

                $cotisations = [
                    'total_cotisations' => $cotisationsMembre->count(),
                    'total_paye'        => $cotisationsMembre->where('statut', 'paye')->count(),
                    'total_non_paye'    => $cotisationsMembre->whereIn('statut', ['non_paye', 'reste'])->count(),
                    'montant_total'     => round((float) $montantTotal, 2),
                    'montant_restant'   => (float) $cotisationsMembre->whereIn('statut', ['non_paye', 'reste'])->sum('montant_restant'),
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'membres'     => [
                        'bureau'  => $bureau,
                        'membres' => $membres,
                        'total'   => $total,
                    ],
                    'cotisations' => $cotisations,
                    'activites'   => [
                        'total'     => $totalActivites,
                        'en_cours'  => $activitesEnCours,
                        'terminees' => $activitesTerminees,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
            ], 500);
        }
    }

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
            'role'          => 'nullable|string|in:NOVICE,MEMBER,BUREAU',
            'subRoles'      => 'nullable|array',
            'subRoles.*'    => 'string',
        ]);

        try {
            $updateData = [
                'name'  => trim($validated['prenom'] . ' ' . $validated['nom']),
                'email' => $validated['email'],
            ];

            if (isset($validated['role'])) {
                $newRole = $validated['role'];
                $newSubRoles = $newRole === 'BUREAU' ? ($validated['subRoles'] ?? []) : [];

                $updateData['role']     = $newRole;
                $updateData['sub_role'] = json_encode($newSubRoles);
            }

            $member->update($updateData);

            // Synchroniser le rôle dans les emails autorisés (un user = un seul rôle)
            if (isset($validated['role'])) {
                AuthorizedEmail::where('email', $member->email)->update(['role' => $validated['role']]);

                // Recalibrer les cotisations non payées au tarif du nouveau rôle
                $this->recalibrateCotisationAmounts($member, $validated['role']);
            }

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
                $member->setMeta($key, $value);
            }

            // Upload avatar si fourni
            if (!empty($validated['image']) && str_starts_with($validated['image'], 'data:image/')) {
                $imageParts = explode(',', $validated['image']);
                if (count($imageParts) === 2) {
                    $imageData = base64_decode($imageParts[1], true);
                    $publicId  = 'profile_' . $member->id;
                    $avatarUrl = $this->uploadAvatarLocal($imageData, $publicId);
                    $member->update(['avatar' => $avatarUrl]);
                }
            }

            $member->load('meta');

            // Notification globale si changement de rôle / commission
            if (isset($validated['role'])) {
                $newRole = $validated['role'];
                $newSubRoles = $newRole === 'BUREAU' ? ($validated['subRoles'] ?? []) : [];
                $newSubRole = $newSubRoles[0] ?? null;
                $oldRole    = $oldRole ?? null;
                $oldSubRole = $oldSubRole ?? null;

                if ($newRole !== $oldRole || $newSubRole !== $oldSubRole) {
                    $memberName = $member->name ?? ($validated['prenom'] . ' ' . $validated['nom']);
                    $newSubRoleLabel = $this->getSubRoleLabel($newSubRole);
                    $rolePart = $newRole === 'BUREAU'
                        ? ("a rejoint le bureau" . ($newSubRoleLabel ? " — {$newSubRoleLabel}" : ''))
                        : ("a changé de rôle: {$newRole}");

                    $payload = [
                        'message' => "{$memberName} {$rolePart}.",
                        'user_id' => $member->id,
                        'name' => $memberName,
                        'role' => $newRole,
                        'sub_role' => $newSubRole,
                        'sub_role_label' => $newSubRoleLabel,
                    ];

                    $this->notifyAllMembers('member.role_changed', $payload, null);
                }
            }

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
