<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ActiviteController extends Controller
{
    // ─── Helper URL ───────────────────────────────────────────

    private function getUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return env('APP_URL') . '/storage/' . $path;
    }

    // ─── Helper upload image ──────────────────────────────────

    private function uploadImage($file, string $dossier): string
    {
        \Cloudinary\Configuration\Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);

        $api = new \Cloudinary\Api\Upload\UploadApi();
        $result = $api->upload($file->getRealPath(), [
            'folder' => 'aeddi/' . $dossier,
        ]);

        return $result['secure_url'];
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            if (str_starts_with($path, 'http') && str_contains($path, 'cloudinary')) {
                preg_match('/\/aeddi\/.*\/([^.]+)/', $path, $matches);
                if (!empty($matches[0])) {
                    $publicId = ltrim($matches[0], '/');
                    Cloudinary::destroy($publicId);
                }
            } else {
                Storage::disk('public')->delete($path);
            }
        }
    }

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
                ? $this->getUrl($activite->image_lieu)
                : null,
            'categorie'   => $activite->categorie,
            'statut'      => $activite->statut,
            'image'       => $activite->image
                ? $this->getUrl($activite->image)
                : null,
            'galerie'     => collect($activite->getMeta('galerie', []))
                ->map(fn($path) => $this->getUrl($path))
                ->values(),
            'created_at'  => $activite->created_at,
        ];
    }

    // ─── CRUD ─────────────────────────────────────────────────

    public function index()
    {
        $activites = Activite::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $activites->map(fn($a) => $this->format($a)),
        ]);
    }

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

            // Invalidation cache "liste récente" pour le polling
            Cache::forget('activites:latest:5');

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

    public function latest(Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 5);
        $limit = max(1, min($limit, 20));

        // Si le client ne donne pas after_id, on renvoie une petite liste "dernières activités" via cache.
        if ($afterId <= 0) {
            $latest = Cache::remember('activites:latest:5', 10, function () {
                return Activite::orderByDesc('id')->take(5)->get();
            });

            return response()->json([
                'success' => true,
                'data' => $latest->map(fn($a) => $this->format($a)),
                'meta' => [
                    'max_id' => (int) ($latest->first()?->id ?? 0),
                ],
            ]);
        }

        // Sinon: on ne renvoie QUE les nouveautés (très rapide, pas de scan complet).
        $news = Activite::where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $news->map(fn($a) => $this->format($a)),
            'meta' => [
                'max_id' => (int) ($news->last()?->id ?? $afterId),
                'count' => $news->count(),
            ],
        ]);
    }

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

        if ($request->hasFile('image')) {
            $this->deleteImage($activite->image);
            $activite->image = $this->uploadImage($request->file('image'), 'activites');
        }

        if ($request->hasFile('image_lieu')) {
            $this->deleteImage($activite->image_lieu);
            $activite->image_lieu = $this->uploadImage($request->file('image_lieu'), 'activites/lieux');
        }

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
                ->map(fn($path) => $this->getUrl($path))
                ->values(),
        ]);
    }
}
