<?php

namespace App\Http\Controllers;

use App\Models\Tache;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TacheController extends Controller
{
    private function format(Tache $tache): array
    {
        return [
            'id'           => $tache->id,
            'titre'        => $tache->titre,
            'description'  => $tache->description,
            'date_debut'   => $tache->date_debut?->toDateString(),
            'assigned_by'  => $tache->assignedBy?->only(['id', 'name', 'avatar', 'sub_role']),
            'assigned_to'  => $tache->assignedTo?->only(['id', 'name', 'avatar', 'sub_role']),
            'statut'       => $tache->statut,
            'priorite'     => $tache->priorite,
            'date_echeance'=> $tache->date_echeance?->toDateString(),
            'created_at'   => $tache->created_at,
            'updated_at'   => $tache->updated_at,
        ];
    }

    private function notifyTacheCreated(Request $request, Tache $tache): void
    {
        $actor = $request->user();
        $actorName = $actor?->name ?? 'Un administrateur';

        $payload = [
            'message' => "{$actorName} vous a assigné la tâche « {$tache->titre} ».",
            'tache_id' => $tache->id,
            'titre' => $tache->titre,
            'priorite' => $tache->priorite,
            'date_echeance' => $tache->date_echeance?->toDateString(),
        ];

        UserNotification::create([
            'user_id' => $tache->assigned_to,
            'type' => 'tache.created',
            'data' => $payload,
        ]);
    }

    public function index(Request $request)
    {
        $query = Tache::with(['assignedBy', 'assignedTo'])->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $taches = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $taches->map(fn($t) => $this->format($t)),
        ]);
    }

    public function myTasks(Request $request)
    {
        $user = $request->user();

        $query = Tache::with(['assignedBy', 'assignedTo'])
            ->where('assigned_to', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $taches = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $taches->map(fn($t) => $this->format($t)),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'date_debut'   => 'nullable|date',
            'assigned_to'  => 'required|exists:users,id',
            'priorite'     => 'required|in:basse,moyenne,haute,urgente',
            'date_echeance'=> 'nullable|date',
            'statut'       => 'nullable|in:en_attente,en_cours,terminee,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $tache = Tache::create([
                'titre'        => $request->titre,
                'description'  => $request->description,
                'date_debut'   => $request->date_debut,
                'assigned_by'  => $request->user()->id,
                'assigned_to'  => $request->assigned_to,
                'priorite'     => $request->priorite,
                'date_echeance'=> $request->date_echeance,
                'statut'       => $request->statut ?? 'en_attente',
            ]);

            $tache->load(['assignedBy', 'assignedTo']);

            $this->notifyTacheCreated($request, $tache);

            return response()->json([
                'success' => true,
                'message' => 'Tâche créée avec succès',
                'data'    => $this->format($tache),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la tâche',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $tache = Tache::with(['assignedBy', 'assignedTo'])->find($id);

        if (!$tache) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->format($tache),
        ]);
    }

    public function update(Request $request, $id)
    {
        $tache = Tache::find($id);

        if (!$tache) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'titre'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'date_debut'   => 'nullable|date',
            'assigned_to'  => 'sometimes|exists:users,id',
            'priorite'     => 'sometimes|in:basse,moyenne,haute,urgente',
            'date_echeance'=> 'nullable|date',
            'statut'       => 'sometimes|in:en_attente,en_cours,terminee,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $tache->update($request->only([
                'titre',
                'description',
                'date_debut',
                'assigned_to',
                'priorite',
                'date_echeance',
                'statut',
            ]));

            $tache->load(['assignedBy', 'assignedTo']);

            return response()->json([
                'success' => true,
                'message' => 'Tâche mise à jour avec succès',
                'data'    => $this->format($tache),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la tâche',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $tache = Tache::find($id);

        if (!$tache) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée',
            ], 404);
        }

        $tache->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès',
        ]);
    }
}
