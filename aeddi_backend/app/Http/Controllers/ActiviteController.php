<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActiviteController extends Controller
{
    /**
     * Afficher toutes les activités (pour tous les utilisateurs)
     */
    public function index()
    {
        $activites = Activite::with('membres')->orderBy('date_debut', 'desc')->get();

        $activitesData = $activites->map(function ($activite) {
            $totalMembres = $activite->membres->count();
            $enCours = $activite->membres->where('pivot.statut_participation', 'en_cours')->count();
            $terminees = $activite->membres->where('pivot.statut_participation', 'terminee')->count();
            $enAttente = $activite->membres->where('pivot.statut_participation', 'en_attente')->count();
            $annulees = $activite->membres->where('pivot.statut_participation', 'annulee')->count();

            return [
                'id' => $activite->id,
                'nom' => $activite->nom,
                'description' => $activite->description,
                'date_debut' => $activite->date_debut,
                'date_fin' => $activite->date_fin,
                'statut' => $activite->statut,
                'total_membres' => $totalMembres,
                'membres_en_cours' => $enCours,
                'membres_terminees' => $terminees,
                'membres_en_attente' => $enAttente,
                'membres_annulees' => $annulees,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $activitesData
        ]);
    }

    /**
     * Créer une nouvelle activité (admin seulement)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'required|in:en_cours,terminee,en_attente,annulee'
        ]);

        try {
            DB::beginTransaction();

            // Créer l'activité
            $activite = Activite::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'statut' => $request->statut
            ]);

            // Associer l'activité à tous les membres (sauf l'admin)
            $membres = User::where('email', '!=', 'admin@aeddi.com')->get();
            foreach ($membres as $membre) {
                $activite->membres()->attach($membre->id, [
                    'statut_participation' => 'en_cours',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Activité créée et associée à tous les membres avec succès',
                'data' => $activite
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une activité spécifique
     */
    public function show($id)
    {
        $activite = Activite::with('membres')->find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activite
        ]);
    }

    /**
     * Mettre à jour une activité (admin seulement)
     */
    public function update(Request $request, $id)
    {
        $activite = Activite::find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
            'statut' => 'sometimes|in:en_cours,terminee,en_attente,annulee'
        ]);

        $activite->update($request->only(['nom', 'description', 'date_debut', 'date_fin', 'statut']));

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour avec succès',
            'data' => $activite
        ]);
    }

    /**
     * Supprimer une activité (admin seulement)
     */
    public function destroy($id)
    {
        $activite = Activite::find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        $activite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès'
        ]);
    }

    /**
     * Mettre à jour le statut de participation d'un membre pour une activité
     */
    public function updateMemberParticipation(Request $request, $activiteId, $userId)
    {
        $request->validate([
            'statut_participation' => 'required|in:en_cours,terminee,en_attente,annulee',
            'commentaire' => 'nullable|string'
        ]);

        $activite = Activite::find($activiteId);
        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        $membre = User::find($userId);
        if (!$membre) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }

        // Vérifier que le membre est associé à cette activité
        if (!$activite->membres()->where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce membre n\'est pas associé à cette activité'
            ], 400);
        }

        // Mettre à jour le statut de participation
        $activite->membres()->updateExistingPivot($userId, [
            'statut_participation' => $request->statut_participation,
            'commentaire' => $request->commentaire,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut de participation mis à jour avec succès'
        ]);
    }
}
