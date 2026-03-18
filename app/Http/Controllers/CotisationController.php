<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use Illuminate\Http\Request;

class CotisationController extends Controller
{
    /**
     * Afficher toutes les cotisations
     */
    public function index()
    {
        $cotisations = Cotisation::with('metas')->orderBy('created_at', 'desc')->get();

        $data = $cotisations->map(function ($cotisation) {
            return [
                'id'          => $cotisation->id,
                'statut'      => $cotisation->statut,
                'nom'         => $cotisation->getMeta('nom'),
                'description' => $cotisation->getMeta('description'),
                'montant'     => $cotisation->getMeta('montant'),
                'date_debut'  => $cotisation->getMeta('date_debut'),
                'date_fin'    => $cotisation->getMeta('date_fin'),
                'created_at'  => $cotisation->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    /**
     * Créer une nouvelle cotisation
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'         => 'required|string|max:255',
            'description' => 'required|string',
            'montant'     => 'required|numeric|min:0',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after:date_debut',
            'statut'      => 'in:active,terminee,annulee,en_preparation',
        ]);

        try {
            $cotisation = Cotisation::create([
                'statut' => $request->statut ?? 'en_preparation',
            ]);

            $cotisation->setMetas([
                'nom'         => $request->nom,
                'description' => $request->description,
                'montant'     => $request->montant,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotisation créée avec succès',
                'data'    => [
                    'id'          => $cotisation->id,
                    'statut'      => $cotisation->statut,
                    'nom'         => $cotisation->getMeta('nom'),
                    'description' => $cotisation->getMeta('description'),
                    'montant'     => $cotisation->getMeta('montant'),
                    'date_debut'  => $cotisation->getMeta('date_debut'),
                    'date_fin'    => $cotisation->getMeta('date_fin'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la cotisation',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une cotisation spécifique
     */
    public function show($id)
    {
        $cotisation = Cotisation::with('metas')->find($id);

        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $cotisation->id,
                'statut'      => $cotisation->statut,
                'nom'         => $cotisation->getMeta('nom'),
                'description' => $cotisation->getMeta('description'),
                'montant'     => $cotisation->getMeta('montant'),
                'date_debut'  => $cotisation->getMeta('date_debut'),
                'date_fin'    => $cotisation->getMeta('date_fin'),
                'created_at'  => $cotisation->created_at,
            ]
        ]);
    }

    /**
     * Mettre à jour une cotisation
     */
    public function update(Request $request, $id)
    {
        $cotisation = Cotisation::with('metas')->find($id);

        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }

        $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'montant'     => 'sometimes|numeric|min:0',
            'date_debut'  => 'sometimes|date',
            'date_fin'    => 'sometimes|date|after:date_debut',
            'statut'      => 'sometimes|in:active,terminee,annulee,en_preparation',
        ]);

        // Mettre à jour le statut si fourni
        if ($request->has('statut')) {
            $cotisation->update(['statut' => $request->statut]);
        }

        // Mettre à jour les metas envoyées
        $metas = $request->only(['nom', 'description', 'montant', 'date_debut', 'date_fin']);
        if (!empty($metas)) {
            $cotisation->setMetas($metas);
        }

        // Recharger les metas
        $cotisation->load('metas');

        return response()->json([
            'success' => true,
            'message' => 'Cotisation mise à jour avec succès',
            'data'    => [
                'id'          => $cotisation->id,
                'statut'      => $cotisation->statut,
                'nom'         => $cotisation->getMeta('nom'),
                'description' => $cotisation->getMeta('description'),
                'montant'     => $cotisation->getMeta('montant'),
                'date_debut'  => $cotisation->getMeta('date_debut'),
                'date_fin'    => $cotisation->getMeta('date_fin'),
            ]
        ]);
    }

    /**
     * Supprimer une cotisation
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
}