<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActiviteController extends Controller
{
    /**
     * Afficher toutes les activités
     */
    public function index()
    {
        $activites = Activite::with('metas')->orderBy('created_at', 'desc')->get();

        $data = $activites->map(function ($activite) {
            return [
                'id'          => $activite->id,
                'statut'      => $activite->statut,
                'nom'         => $activite->getMeta('nom'),
                'description' => $activite->getMeta('description'),
                'date_debut'  => $activite->getMeta('date_debut'),
                'date_fin'    => $activite->getMeta('date_fin'),
                'lieu'        => $activite->getMeta('lieu'),
                'categorie'   => $activite->getMeta('categorie'),
                'image'       => $activite->getMeta('image')
                                    ? asset('storage/' . $activite->getMeta('image'))
                                    : null,
                'created_at'  => $activite->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data
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
            $activite = Activite::create([
                'statut' => $request->statut,
            ]);

            // Upload image si présente
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('activites', 'public');
            }

            $activite->setMetas([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
                'lieu'        => $request->lieu,
                'categorie'   => $request->categorie ?? 'Autre',
                'image'       => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activité créée avec succès',
                'data'    => [
                    'id'          => $activite->id,
                    'statut'      => $activite->statut,
                    'nom'         => $activite->getMeta('nom'),
                    'description' => $activite->getMeta('description'),
                    'date_debut'  => $activite->getMeta('date_debut'),
                    'date_fin'    => $activite->getMeta('date_fin'),
                    'lieu'        => $activite->getMeta('lieu'),
                    'categorie'   => $activite->getMeta('categorie'),
                    'image'       => $imagePath ? asset('storage/' . $imagePath) : null,
                ]
            ], 201);

        } catch (\Exception $e) {
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
        $activite = Activite::with('metas')->find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $activite->id,
                'statut'      => $activite->statut,
                'nom'         => $activite->getMeta('nom'),
                'description' => $activite->getMeta('description'),
                'date_debut'  => $activite->getMeta('date_debut'),
                'date_fin'    => $activite->getMeta('date_fin'),
                'lieu'        => $activite->getMeta('lieu'),
                'categorie'   => $activite->getMeta('categorie'),
                'image'       => $activite->getMeta('image')
                                    ? asset('storage/' . $activite->getMeta('image'))
                                    : null,
                'created_at'  => $activite->created_at,
            ]
        ]);
    }

    /**
     * Mettre à jour une activité
     */
    public function update(Request $request, $id)
    {
        $activite = Activite::with('metas')->find($id);

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

        // Mettre à jour le statut si fourni
        if ($request->has('statut')) {
            $activite->update(['statut' => $request->statut]);
        }

        // Upload nouvelle image et suppression de l'ancienne
        if ($request->hasFile('image')) {
            $ancienneImage = $activite->getMeta('image');
            if ($ancienneImage) {
                Storage::disk('public')->delete($ancienneImage);
            }
            $activite->setMeta('image', $request->file('image')->store('activites', 'public'));
        }

        // Mettre à jour les metas envoyées
        $metas = $request->only(['nom', 'description', 'date_debut', 'date_fin', 'lieu', 'categorie']);
        if (!empty($metas)) {
            $activite->setMetas($metas);
        }

        // Recharger les metas
        $activite->load('metas');

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour avec succès',
            'data'    => [
                'id'          => $activite->id,
                'statut'      => $activite->statut,
                'nom'         => $activite->getMeta('nom'),
                'description' => $activite->getMeta('description'),
                'date_debut'  => $activite->getMeta('date_debut'),
                'date_fin'    => $activite->getMeta('date_fin'),
                'lieu'        => $activite->getMeta('lieu'),
                'categorie'   => $activite->getMeta('categorie'),
                'image'       => $activite->getMeta('image')
                                    ? asset('storage/' . $activite->getMeta('image'))
                                    : null,
            ]
        ]);
    }

    /**
     * Supprimer une activité
     */
    public function destroy($id)
    {
        $activite = Activite::with('metas')->find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée'
            ], 404);
        }

        // Supprimer l'image associée
        $image = $activite->getMeta('image');
        if ($image) {
            Storage::disk('public')->delete($image);
        }

        // Les metas sont supprimées automatiquement via onDelete('cascade')
        $activite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès'
        ]);
    }
}