<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActiviteController extends Controller
{
    /**
     * Afficher toutes les activités
     */
    public function index()
    {
        $activites = Activite::with('membres')->orderBy('date_debut', 'desc')->get();

        $activitesData = $activites->map(function ($activite) {
            $totalMembres = $activite->membres->count();
            $enCours      = $activite->membres->where('pivot.statut_participation', 'en_cours')->count();
            $terminees    = $activite->membres->where('pivot.statut_participation', 'terminee')->count();
            $enAttente    = $activite->membres->where('pivot.statut_participation', 'en_attente')->count();
            $annulees     = $activite->membres->where('pivot.statut_participation', 'annulee')->count();

            return [
                'id'               => $activite->id,
                'nom'              => $activite->nom,
                'description'      => $activite->description,
                'date_debut'       => $activite->date_debut,
                'date_fin'         => $activite->date_fin,
                'statut'           => $activite->statut,
                'lieu'             => $activite->lieu,
                'categorie'        => $activite->categorie,
                'image'            => $activite->image
                                        ? asset('storage/' . $activite->image)
                                        : null,
                'total_membres'    => $totalMembres,
                'membres_en_cours' => $enCours,
                'membres_terminees'=> $terminees,
                'membres_en_attente'=> $enAttente,
                'membres_annulees' => $annulees,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $activitesData
        ]);
    }

    /**
     * Créer une nouvelle activité
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'         => 'required|string|max:255',
            'description' => 'required|string',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'statut'      => 'required|in:en_cours,terminee,en_attente,annulee',
            'lieu'        => 'required|string|max:255',
            'categorie'   => 'nullable|string|max:100',
            'image'       => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Upload image si présente
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('activites', 'public');
            }

            $activite = Activite::create([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
                'statut'      => $request->statut,
                'lieu'        => $request->lieu,
                'categorie'   => $request->categorie ?? 'Autre',
                'image'       => $imagePath,
            ]);

            // Associer à tous les membres (sauf admin)
            $membres = User::where('email', '!=', 'admin@aeddi.com')->get();
            foreach ($membres as $membre) {
                $activite->membres()->attach($membre->id, [
                    'statut_participation' => 'en_cours',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Activité créée et associée à tous les membres avec succès',
                'data'    => array_merge($activite->toArray(), [
                    'image' => $imagePath ? asset('storage/' . $imagePath) : null,
                ]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'activité',
                'error'   => $e->getMessage()
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
            'data'    => array_merge($activite->toArray(), [
                'image' => $activite->image ? asset('storage/' . $activite->image) : null,
            ]),
        ]);
    }

    /**
     * Mettre à jour une activité
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
            'nom'         => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date_debut'  => 'sometimes|date',
            'date_fin'    => 'sometimes|date|after_or_equal:date_debut',
            'statut'      => 'sometimes|in:en_cours,terminee,en_attente,annulee',
            'lieu'        => 'sometimes|string|max:255',
            'categorie'   => 'nullable|string|max:100',
            'image'       => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Upload nouvelle image et suppression de l'ancienne
        if ($request->hasFile('image')) {
            if ($activite->image) {
                Storage::disk('public')->delete($activite->image);
            }
            $activite->image = $request->file('image')->store('activites', 'public');
        }

        $activite->fill($request->only([
            'nom', 'description', 'date_debut', 'date_fin', 'statut', 'lieu', 'categorie'
        ]));
        $activite->save();

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour avec succès',
            'data'    => array_merge($activite->toArray(), [
                'image' => $activite->image ? asset('storage/' . $activite->image) : null,
            ]),
        ]);
    }

    /**
     * Supprimer une activité
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

        // Supprimer l'image associée
        if ($activite->image) {
            Storage::disk('public')->delete($activite->image);
        }

        $activite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès'
        ]);
    }

    /**
     * Mettre à jour le statut de participation d'un membre
     */
    public function updateMemberParticipation(Request $request, $activiteId, $userId)
    {
        $request->validate([
            'statut_participation' => 'required|in:en_cours,terminee,en_attente,annulee',
            'commentaire'          => 'nullable|string'
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

        if (!$activite->membres()->where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce membre n\'est pas associé à cette activité'
            ], 400);
        }

        $activite->membres()->updateExistingPivot($userId, [
            'statut_participation' => $request->statut_participation,
            'commentaire'          => $request->commentaire,
            'updated_at'           => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut de participation mis à jour avec succès'
        ]);
    }
}