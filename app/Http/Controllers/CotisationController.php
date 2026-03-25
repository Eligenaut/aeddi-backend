<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\CotisationMembre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CotisationController extends Controller
{
    // ─── Helper format réponse ────────────────────────────────
    private function format(Cotisation $cotisation): array
    {
        return [
            'id'            => $cotisation->id,
            'nom'           => $cotisation->nom,
            'description'   => $cotisation->description,
            'montant_novice' => $cotisation->montant_novice,
            'montant_ancien' => $cotisation->montant_ancien,
            'date_debut'    => $cotisation->date_debut->toDateString(),
            'date_fin'      => $cotisation->date_fin->toDateString(),
            'statut'        => $cotisation->statut,
            'created_at'    => $cotisation->created_at,
            'total_membres' => $cotisation->total_membres ?? 0,
            'membres_payes' => $cotisation->membres_payes ?? 0,
        ];
    }

    // ─── Afficher toutes les cotisations ─────────────────────
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié',
                ], 401);
            }
            if ($user->role === 'ADMIN') {
                $cotisations = Cotisation::withCount([
                    'cotisationMembres as total_membres',
                    'cotisationMembres as membres_payes' => function ($query) {
                        $query->where('statut', 'paye');
                    },
                ])->orderBy('created_at', 'desc')->get();

                return response()->json([
                    'success' => true,
                    'data'    => $cotisations->map(fn($c) => $this->format($c)),
                ]);
            }

            $membre = User::find($user->id);

            if (!$membre) {
                return response()->json([
                    'success' => false,
                    'message' => 'Membre non trouvé',
                ], 404);
            }

            $cotisations = CotisationMembre::with('cotisation')
                ->where('user_id', $membre->id)
                ->get()
                ->map(fn($cm) => [
                    'cotisation' => [
                        'id'      => $cm->cotisation->id,
                        'nom'     => $cm->cotisation->nom,
                        'montant' => $membre->role === 'NOVICE'
                            ? $cm->cotisation->montant_novice
                            : $cm->cotisation->montant_ancien,
                    ],
                    'statut'          => $cm->statut,
                    'montant_restant' => $cm->montant_restant,
                ]);

            return response()->json([
                'success' => true,
                'data'    => $cotisations,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur index cotisations', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Créer une cotisation ───────────────────────────────
    public function store(Request $request)
    {
        Log::info('Cotisation store - données reçues', $request->all());

        $request->validate([
            'nom'            => 'required|string|max:255',
            'description'    => 'required|string',
            'montant_novice' => 'required|numeric|min:0',
            'montant_ancien' => 'required|numeric|min:0',
            'date_debut'     => 'required|date',
            'date_fin'       => 'required|date|after:date_debut',
            'statut'         => 'sometimes|in:en_cours,terminee,en_attente,annulee',
        ]);

        try {
            $cotisation = Cotisation::create([
                'nom'             => $request->nom,
                'description'     => $request->description,
                'montant_novice'  => $request->montant_novice,
                'montant_ancien'  => $request->montant_ancien,
                'date_debut'      => $request->date_debut,
                'date_fin'        => $request->date_fin,
                'statut'          => $request->statut ?? 'en_cours',
            ]);

            // ✅ Assigner la cotisation à tous les membres sauf ADMIN
            $membres = User::where('role', '!=', 'ADMIN')->get();

            foreach ($membres as $membre) {
                $montant = $membre->role === 'NOVICE'
                    ? $cotisation->montant_novice
                    : $cotisation->montant_ancien;

                CotisationMembre::firstOrCreate(
                    [
                        'user_id'       => $membre->id,
                        'cotisation_id' => $cotisation->id,
                    ],
                    [
                        'statut'          => 'non_paye',
                        'montant_restant' => $montant,
                    ]
                );
            }

            Log::info('Cotisation créée et assignée', [
                'id'               => $cotisation->id,
                'membres_assignes' => $membres->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotisation créée avec succès',
                'data'    => $this->format($cotisation),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur création cotisation', [
                'error' => $e->getMessage(),
                'data'  => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la cotisation',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Afficher une cotisation spécifique ─────────────────
    public function show($id)
    {
        $cotisation = Cotisation::find($id);
        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->format($cotisation),
        ]);
    }

    // ─── Mettre à jour une cotisation ──────────────────────
    public function update(Request $request, $id)
    {
        $cotisation = Cotisation::find($id);
        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée',
            ], 404);
        }

        $request->validate([
            'nom'            => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'montant_novice' => 'sometimes|numeric|min:0',
            'montant_ancien' => 'sometimes|numeric|min:0',
            'date_debut'     => 'sometimes|date',
            'date_fin'       => 'sometimes|date|after:date_debut',
            'statut'         => 'sometimes|in:en_cours,terminee,en_attente,annulee',
        ]);

        $cotisation->update($request->only([
            'nom',
            'description',
            'montant_novice',
            'montant_ancien',
            'date_debut',
            'date_fin',
            'statut'
        ]));

        // 🔄 Mettre à jour les montants restants pour chaque membre assigné
        foreach ($cotisation->cotisationMembres as $cm) {
            $montant = $cm->user->role === 'NOVICE'
                ? $cotisation->montant_novice
                : $cotisation->montant_ancien;

            $cm->update(['montant_restant' => $montant]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cotisation mise à jour avec succès',
            'data'    => $this->format($cotisation),
        ]);
    }

    // ─── Supprimer une cotisation ───────────────────────────
    public function destroy($id)
    {
        $cotisation = Cotisation::find($id);
        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée',
            ], 404);
        }

        $cotisation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotisation supprimée avec succès',
        ]);
    }

    // ─── Récupérer cotisations d’un membre ─────────────────
    public function getMemberCotisations($memberId)
    {
        $membre = User::find($memberId);
        if (!$membre) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé',
            ], 404);
        }

        $cotisations = CotisationMembre::with('cotisation')
            ->where('user_id', $memberId)
            ->get()
            ->map(fn($cm) => [
                'cotisation'      => [
                    'id'           => $cm->cotisation->id,
                    'nom'          => $cm->cotisation->nom,
                    'montant'      => $cm->user->role === 'NOVICE'
                        ? $cm->cotisation->montant_novice
                        : $cm->cotisation->montant_ancien,
                ],
                'statut'          => $cm->statut,
                'montant_restant' => $cm->montant_restant,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $cotisations,
        ]);
    }

    // ─── Mettre à jour le statut d'une cotisation pour un membre ──────
    public function updateMemberStatus(Request $request, $cotisationId, $memberId)
    {
        $request->validate([
            'statut'          => 'required|in:paye,non_paye,reste',
            'montant_restant' => 'sometimes|numeric|min:0',
        ]);

        $cotisationMembre = CotisationMembre::where('cotisation_id', $cotisationId)
            ->where('user_id', $memberId)
            ->first();

        if (!$cotisationMembre) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation membre non trouvée',
            ], 404);
        }

        $cotisationMembre->update([
            'statut'          => $request->statut,
            'montant_restant' => $request->statut === 'reste' ? $request->montant_restant : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
        ]);
    }

    // ─── Supprimer une cotisation pour un membre ─────────────
    public function deleteMemberCotisation($cotisationId, $memberId)
    {
        $cotisationMembre = CotisationMembre::where('cotisation_id', $cotisationId)
            ->where('user_id', $memberId)
            ->first();

        if (!$cotisationMembre) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation membre non trouvée',
            ], 404);
        }

        $cotisationMembre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotisation supprimée pour ce membre',
        ]);
    }
}
