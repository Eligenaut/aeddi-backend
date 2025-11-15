<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\CotisationMembre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotisationController extends Controller
{
    /**
     * Afficher toutes les cotisations (pour l'admin)
     */
    public function index()
    {
        $cotisations = Cotisation::with('cotisationMembres')->get();

        $cotisationsData = $cotisations->map(function ($cotisation) {
            $total = $cotisation->cotisationMembres->count();
            $paye = $cotisation->cotisationMembres->where('statut', 'paye')->count();
            $non_paye = $cotisation->cotisationMembres->whereIn('statut', ['non_paye', 'reste'])->count();
            return [
                'id' => $cotisation->id,
                'nom' => $cotisation->nom,
                'description' => $cotisation->description,
                'montant' => $cotisation->montant,
                'date_debut' => $cotisation->date_debut,
                'date_fin' => $cotisation->date_fin,
                'statut' => $cotisation->statut,
                'total_membres' => $total,
                'membres_payes' => $paye,
                'membres_non_payes' => $non_paye,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $cotisationsData
        ]);
    }

    /**
     * Créer une nouvelle cotisation (admin seulement)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'in:active,terminee,annulee,en_preparation'
        ]);

        DB::beginTransaction();
        try {
            // Créer la cotisation
            $cotisation = Cotisation::create($request->all());

            // Dispatcher la cotisation à tous les membres (sauf admin)
            $membres = User::where('email', '!=', 'admin@aeddi.com')->get();
            
            foreach ($membres as $membre) {
                CotisationMembre::create([
                    'user_id' => $membre->id,
                    'cotisation_id' => $cotisation->id,
                    'statut' => 'non_paye',
                    'montant_restant' => $cotisation->montant
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotisation créée et dispatchée avec succès',
                'data' => $cotisation->load('cotisationMembres')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une cotisation spécifique
     */
    public function show($id)
    {
        $cotisation = Cotisation::with(['cotisationMembres.user'])->find($id);

        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cotisation
        ]);
    }

    /**
     * Mettre à jour une cotisation (admin seulement)
     */
    public function update(Request $request, $id)
    {
        $cotisation = Cotisation::find($id);

        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'montant' => 'sometimes|numeric|min:0',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
            'statut' => 'sometimes|in:active,terminee,annulee,en_preparation'
        ]);

        $cotisation->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cotisation mise à jour avec succès',
            'data' => $cotisation
        ]);
    }

    /**
     * Supprimer une cotisation (admin seulement)
     */
    public function destroy($id)
    {
        $cotisation = Cotisation::find($id);

        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }

        $cotisation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotisation supprimée avec succès'
        ]);
    }

    /**
     * Mettre à jour le statut de cotisation d'un membre
     */
    public function updateMemberStatus(Request $request, $cotisationId, $userId)
    {
        $request->validate([
            'statut' => 'required|in:non_paye,paye,reste',
            'montant_restant' => 'nullable|numeric|min:0'
        ]);

        $cotisationMembre = CotisationMembre::where('cotisation_id', $cotisationId)
                                          ->where('user_id', $userId)
                                          ->first();

        if (!$cotisationMembre) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation membre non trouvée'
            ], 404);
        }

        $cotisationMembre->update([
            'statut' => $request->statut,
            'montant_restant' => $request->montant_restant
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $cotisationMembre
        ]);
    }

    /**
     * Obtenir les cotisations du membre connecté
     */
    public function getMyCotisations(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }
        $cotisations = CotisationMembre::with('cotisation')
            ->where('user_id', $user->id)
            ->get();

        // Statistiques demandées côté membre
        $total_cotisations = $cotisations->count();
        $total_paye = $cotisations->where('statut', 'paye')->count();
        $total_non_paye = $cotisations->whereIn('statut', ['non_paye', 'reste'])->count();
        $montant_total_restant = $cotisations->whereIn('statut', ['non_paye', 'reste'])->sum('montant_restant');

        $cotisationsData = $cotisations->map(function ($cotisationMembre) {
            return [
                'id' => $cotisationMembre->cotisation->id,
                'nom' => $cotisationMembre->cotisation->nom,
                'description' => $cotisationMembre->cotisation->description,
                'montant' => $cotisationMembre->cotisation->montant,
                'date_debut' => $cotisationMembre->cotisation->date_debut,
                'date_fin' => $cotisationMembre->cotisation->date_fin,
                'statut' => $cotisationMembre->cotisation->statut,
                'statut_paiement' => $cotisationMembre->statut,
                'montant_restant' => $cotisationMembre->montant_restant
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $cotisationsData,
            'stats' => [
                'total_cotisations' => $total_cotisations,
                'total_paye' => $total_paye,
                'total_non_paye' => $total_non_paye,
                'montant_total_restant' => $montant_total_restant
            ]
        ]);
    }

    /**
     * Obtenir les cotisations d'un membre
     */
    public function getMemberCotisations($userId)
    {
        $cotisations = CotisationMembre::with('cotisation')
                                     ->where('user_id', $userId)
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $cotisations
        ]);
    }

    /**
     * Supprimer une cotisation associée à un membre
     */
    public function deleteMemberCotisation($cotisationId, $userId)
    {
        $cotisationMembre = CotisationMembre::where('cotisation_id', $cotisationId)
            ->where('user_id', $userId)
            ->first();

        if (!$cotisationMembre) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation membre non trouvée'
            ], 404);
        }

        $cotisationMembre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotisation supprimée pour ce membre'
        ]);
    }
}
