<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActiviteController extends Controller
{
    // ─── Helper format réponse ────────────────────────────────

    private function format(Activite $activite): array
    {
        return [
            'id'          => $activite->id,
            'nom'         => $activite->nom,
            'description' => $activite->description,
            'date_debut'  => $activite->date_debut->toDateString(),
            'date_fin'    => $activite->date_fin->toDateString(),
            'lieu'        => $activite->lieu,
            'image_lieu'  => $activite->image_lieu
                ? asset('storage/' . $activite->image_lieu)
                : null,
            'categorie'   => $activite->categorie,
            'statut'      => $activite->statut,
            'image'       => $activite->image
                ? asset('storage/' . $activite->image)
                : null,
            'galerie'     => collect($activite->getMeta('galerie', []))
                ->map(fn($path) => asset('storage/' . $path))
                ->values(),
            'created_at'  => $activite->created_at,
        ];
    }

    // ─── Helper upload image ──────────────────────────────────

    private function uploadImage($file, string $dossier): string
    {
        return $file->store($dossier, 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    // ─── CRUD ─────────────────────────────────────────────────

    /**
     * Afficher toutes les activités
     */
    public function index()
    {
        $activites = Activite::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $activites->map(fn($a) => $this->format($a)),
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
            'image_lieu'  => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'galerie'     => 'nullable|array',
            'galerie.*'   => 'image|mimes:jpeg,png,webp|max:2048',
        ]);

        try {
            $activite = Activite::create([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
                'lieu'        => $request->lieu,
                'categorie'   => $request->categorie ?? 'Autre',
                'statut'      => $request->statut,
                'image'       => $request->hasFile('image')
                    ? $this->uploadImage($request->file('image'), 'activites')
                    : null,
                'image_lieu'  => $request->hasFile('image_lieu')
                    ? $this->uploadImage($request->file('image_lieu'), 'activites/lieux')
                    : null,
            ]);

            if ($request->hasFile('galerie')) {
                $paths = [];
                foreach ($request->file('galerie') as $file) {
                    $paths[] = $this->uploadImage($file, 'activites/galerie');
                }
                $activite->setMeta('galerie', $paths);
            }

            return response()->json([
                'success' => true,
                'message' => 'Activité créée avec succès',
                'data'    => $this->format($activite),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'activité',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une activité spécifique
     */
    public function show($id)
    {
        $activite = Activite::find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->format($activite),
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
                'message' => 'Activité non trouvée',
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
            'image_lieu'  => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'galerie'     => 'nullable|array',
            'galerie.*'   => 'image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Mise à jour image principale
        if ($request->hasFile('image')) {
            $this->deleteImage($activite->image);
            $activite->image = $this->uploadImage($request->file('image'), 'activites');
        }

        // Mise à jour image lieu
        if ($request->hasFile('image_lieu')) {
            $this->deleteImage($activite->image_lieu);
            $activite->image_lieu = $this->uploadImage($request->file('image_lieu'), 'activites/lieux');
        }

        // Ajout images dans la galerie
        if ($request->hasFile('galerie')) {
            $anciens = $activite->getMeta('galerie', []);
            $nouveaux = [];
            foreach ($request->file('galerie') as $file) {
                $nouveaux[] = $this->uploadImage($file, 'activites/galerie');
            }
            $activite->setMeta('galerie', array_merge($anciens, $nouveaux));
        }

        $activite->update($request->only([
            'nom',
            'description',
            'date_debut',
            'date_fin',
            'lieu',
            'categorie',
            'statut',
            'image',
            'image_lieu'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour avec succès',
            'data'    => $this->format($activite),
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
                'message' => 'Activité non trouvée',
            ], 404);
        }

        $this->deleteImage($activite->image);
        $this->deleteImage($activite->image_lieu);

        foreach ($activite->getMeta('galerie', []) as $path) {
            $this->deleteImage($path);
        }

        $activite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès',
        ]);
    }

    // ─── Galerie ──────────────────────────────────────────────

    /**
     * Supprimer une image de la galerie
     */
    public function deleteGalerieImage(int $id, int $index)
    {
        $activite = Activite::find($id);

        if (!$activite) {
            return response()->json([
                'success' => false,
                'message' => 'Activité non trouvée',
            ], 404);
        }

        $galerie = $activite->getMeta('galerie', []);

        if (!isset($galerie[$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée dans la galerie',
            ], 404);
        }

        $this->deleteImage($galerie[$index]);

        array_splice($galerie, $index, 1);
        $activite->setMeta('galerie', array_values($galerie));

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès',
            'galerie' => collect($activite->getMeta('galerie', []))
                ->map(fn($path) => asset('storage/' . $path))
                ->values(),
        ]);
    }
}
