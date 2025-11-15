<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CotisationMembre;
use App\Models\Activite;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * Obtenir la liste des membres avec leurs stats de cotisation
     */
    public function index(Request $request)
    {
        $members = User::select([
            'id',
            'name',
            'email',
            'avatar',
            'google_id',
            'created_at',
            'updated_at'
        ])->where('email', '!=', 'admin@aeddi.com')->get();

        $membersWithStats = $members->map(function ($member) {
            $cotisationStats = CotisationMembre::where('user_id', $member->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = "paye" THEN 1 ELSE 0 END) as payees,
                    SUM(CASE WHEN statut IN ("non_paye", "reste") THEN 1 ELSE 0 END) as non_payees
                ')
                ->first();

            // Montant total des cotisations (somme des montants d'origine)
            $totalMontant = CotisationMembre::where('user_id', $member->id)
                ->join('cotisations', 'cotisation_membre.cotisation_id', '=', 'cotisations.id')
                ->sum('cotisations.montant');

            // Montant payé = somme des montants pour les cotisations payées
            $montantPaye = CotisationMembre::where('user_id', $member->id)
                ->join('cotisations', 'cotisation_membre.cotisation_id', '=', 'cotisations.id')
                ->where('cotisation_membre.statut', 'paye')
                ->sum('cotisations.montant');

            // Montant non payé = somme des montants restants pour les cotisations non payées ou reste
            $montantNonPaye = CotisationMembre::where('user_id', $member->id)
                ->whereIn('statut', ['non_paye', 'reste'])
                ->sum('montant_restant');

            $member->cotisation_stats = [
                'total' => $cotisationStats->total ?? 0,
                'payees' => $cotisationStats->payees ?? 0,
                'non_payees' => $cotisationStats->non_payees ?? 0,
                'total_montant' => $totalMontant ?? 0,
                'montant_paye' => $montantPaye ?? 0,
                'montant_non_paye' => $montantNonPaye ?? 0
            ];

            return $member;
        });

        return response()->json([
            'success' => true,
            'data' => $membersWithStats,
            'total' => $membersWithStats->count()
        ]);
    }

    /**
     * Obtenir les statistiques des membres (excluant l'admin)
     */
    public function stats()
    {
        $totalMembers = User::where('email', '!=', 'admin@aeddi.com')->count();
        $googleMembers = User::whereNotNull('google_id')->where('email', '!=', 'admin@aeddi.com')->count();
        $classicMembers = User::whereNull('google_id')->where('email', '!=', 'admin@aeddi.com')->count();
        
        // Membres créés ce mois
        $thisMonth = User::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->where('email', '!=', 'admin@aeddi.com')
                        ->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => $totalMembers,
                'google' => $googleMembers,
                'classic' => $classicMembers,
                'this_month' => $thisMonth
            ]
        ]);
    }

    /**
     * Obtenir les statistiques globales des cotisations pour l'admin
     */
    public function cotisationStats()
    {
        // Statistiques globales des cotisations
        $totalCotisations = CotisationMembre::count();
        $cotisationsPayees = CotisationMembre::where('statut', 'paye')->count();
        $cotisationsNonPayees = CotisationMembre::whereIn('statut', ['non_paye', 'reste'])->count();
        
        // Montants
        $montantTotal = CotisationMembre::join('cotisations', 'cotisation_membre.cotisation_id', '=', 'cotisations.id')
            ->sum('cotisations.montant');
            
        $montantPaye = CotisationMembre::join('cotisations', 'cotisation_membre.cotisation_id', '=', 'cotisations.id')
            ->where('cotisation_membre.statut', 'paye')
            ->sum('cotisations.montant');
            
        $montantNonPaye = CotisationMembre::whereIn('statut', ['non_paye', 'reste'])
            ->sum('montant_restant');

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => $totalCotisations,
                'payees' => $cotisationsPayees,
                'non_payees' => $cotisationsNonPayees,
                'montant_total' => $montantTotal,
                'montant_paye' => $montantPaye,
                'montant_non_paye' => $montantNonPaye
            ]
        ]);
    }

    /**
     * Obtenir un membre spécifique
     */
    public function show($id)
    {
        $member = User::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }

        // S'assurer que l'avatar est une URL complète
        $avatarUrl = $member->avatar;
        if (!$avatarUrl && $member->profile_image) {
            $avatarUrl = asset('storage/' . $member->profile_image);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'nom' => $member->nom,
                'prenom' => $member->prenom,
                'email' => $member->email,
                'avatar' => $avatarUrl,
                'google_id' => $member->google_id,
                'role' => $member->role ?? 'member',
                'etablissement' => $member->etablissement,
                'parcours' => $member->parcours,
                'niveau' => $member->niveau,
                'promotion' => $member->promotion,
                'logement' => $member->logement,
                'blocCampus' => $member->bloc_campus,
                'quartier' => $member->quartier,
                'telephone' => $member->telephone,
                'profile_image' => $member->profile_image,
                'statut' => $member->email_verified_at ? 'actif' : 'en_attente',
                'created_at' => $member->created_at->toDateTimeString(),
                'updated_at' => $member->updated_at->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Mettre à jour un membre (admin seulement)
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'telephone' => 'nullable|string|max:20',
            'etablissement' => 'nullable|string|max:255',
            'parcours' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:255',
            'promotion' => 'nullable|string|max:255',
            'logement' => 'nullable|in:campus,ville',
            'blocCampus' => 'nullable|string|max:255',
            'quartier' => 'nullable|string|max:255',
            'role' => 'nullable|in:admin,bureau,member',
            'sub_role' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'imageName' => 'nullable|string',
            'imageType' => 'nullable|string',
        ]);

        try {
            $filledRole = $validated['role'] ?? $user->role;
            $filledSubRole = ($filledRole === 'bureau') ? ($validated['sub_role'] ?? null) : null;

            $user->fill([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                'etablissement' => $validated['etablissement'],
                'parcours' => $validated['parcours'],
                'niveau' => $validated['niveau'],
                'promotion' => $validated['promotion'],
                'logement' => $validated['logement'],
                'bloc_campus' => $validated['blocCampus'],
                'quartier' => $validated['quartier'],
                'role' => $filledRole,
                'sub_role' => $filledSubRole,
            ]);

            if (isset($validated['image']) && $validated['image']) {
                $imageData = $validated['image'];
                $imageName = $validated['imageName'] ?? 'profile_' . time() . '.jpg';
                
                $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
                $imageData = str_replace('data:image/png;base64,', '', $imageData);
                $imageData = str_replace('data:image/jpg;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                
                $image = base64_decode($imageData);
                $path = 'profile_images/' . $imageName;
                
                \Storage::disk('public')->put($path, $image);
                $user->profile_image = $path;
            }

            $user->save();

            $avatarUrl = $user->avatar;
            if (!$avatarUrl && $user->profile_image) {
                $avatarUrl = asset('storage/' . $user->profile_image);
            }

            return response()->json([
                'success' => true,
                'message' => 'Membre mis à jour avec succès',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'avatar' => $avatarUrl,
                    'google_id' => $user->google_id,
                'role' => $user->role ?? 'member',
                'sub_role' => $user->sub_role,
                'etablissement' => $user->etablissement,
                    'parcours' => $user->parcours,
                    'niveau' => $user->niveau,
                    'promotion' => $user->promotion,
                    'logement' => $user->logement,
                    'blocCampus' => $user->bloc_campus,
                    'quartier' => $user->quartier,
                    'telephone' => $user->telephone,
                    'profile_image' => $user->profile_image,
                    'statut' => $user->email_verified_at ? 'actif' : 'en_attente',
                    'created_at' => $user->created_at->toDateTimeString(),
                    'updated_at' => $user->updated_at->toDateTimeString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du membre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un membre
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user || $user->email === 'admin@aeddi.com') {
            return response()->json([
                'success' => false,
                'message' => 'Suppression impossible.'
            ], 403);
        }
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Membre supprimé avec succès.'
        ]);
    }

    /**
     * Obtenir les statistiques globales du dashboard
     */
    public function dashboardStats()
    {
        $totalMembers = User::where('email', '!=', 'admin@aeddi.com')->count();
        $bureauMembers = User::where('email', '!=', 'admin@aeddi.com')
            ->where('role', 'bureau')
            ->count();
        $regularMembers = $totalMembers - $bureauMembers;

        $totalActivities = Activite::count();
        $enCoursActivities = Activite::where('statut', 'en_cours')->count();
        $termineesActivities = Activite::where('statut', 'terminee')->count();

        $cotisationStats = CotisationMembre::join('cotisations', 'cotisation_membre.cotisation_id', '=', 'cotisations.id')
            ->selectRaw('
                COUNT(*) as total_cotisations,
                SUM(CASE WHEN cotisation_membre.statut = "paye" THEN 1 ELSE 0 END) as total_paye,
                SUM(CASE WHEN cotisation_membre.statut IN ("non_paye", "reste") THEN 1 ELSE 0 END) as total_non_paye,
                SUM(CASE WHEN cotisation_membre.statut = "reste" THEN 1 ELSE 0 END) as total_reste,
                SUM(CASE WHEN cotisation_membre.statut = "paye" THEN cotisations.montant ELSE 0 END) as montant_paye,
                SUM(CASE WHEN cotisation_membre.statut IN ("non_paye", "reste") THEN cotisations.montant ELSE 0 END) as montant_non_paye,
                SUM(CASE WHEN cotisation_membre.statut = "reste" THEN cotisation_membre.montant_restant ELSE 0 END) as montant_restant
            ')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'membres' => [
                    'total' => $totalMembers,
                    'bureau' => $bureauMembers,
                    'membres' => $regularMembers
                ],
                'activites' => [
                    'total' => $totalActivities,
                    'en_cours' => $enCoursActivities,
                    'terminees' => $termineesActivities
                ],
                'cotisations' => [
                    'total_cotisations' => $cotisationStats->total_cotisations ?? 0,
                    'total_paye' => $cotisationStats->total_paye ?? 0,
                    'total_non_paye' => $cotisationStats->total_non_paye ?? 0,
                    'total_reste' => $cotisationStats->total_reste ?? 0,
                    'montant_paye' => $cotisationStats->montant_paye ?? 0,
                    'montant_non_paye' => $cotisationStats->montant_non_paye ?? 0,
                    'montant_restant' => $cotisationStats->montant_restant ?? 0
                ]
            ]
        ]);
    }
}
