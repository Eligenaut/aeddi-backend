<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Http\Request;

class AccueilController extends Controller
{
    // ─── Helper URL ───────────────────────────────────────────

    private function getUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return env('APP_URL') . '/storage/' . $path;
    }

    // ─── Activités publiques ──────────────────────────────────

    public function activites()
    {
        $activites = Activite::orderBy('date_debut', 'desc')
            ->get()
            ->map(function ($activite) {
                return [
                    'id'          => $activite->id,
                    'nom'         => $activite->nom,
                    'description' => $activite->description,
                    'date_debut'  => $activite->date_debut,
                    'date_fin'    => $activite->date_fin,
                    'statut'      => $activite->statut,
                    'lieu'        => $activite->lieu,
                    'categorie'   => $activite->categorie ?? 'Autre',
                    'image'       => $this->getUrl($activite->image),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $activites
        ]);
    }

    // ─── Membres du bureau ────────────────────────────────────

    public function bureau()
    {
        $membres = User::where('role', 'bureau')
            ->orderBy('sub_role')
            ->get()
            ->map(function ($user) {
                $photo = $this->getUrl($user->avatar)
                    ?? $this->getUrl($user->profile_image);

                return [
                    'id'            => $user->id,
                    'nom'           => $user->nom,
                    'prenom'        => $user->prenom,
                    'name'          => $user->name,
                    'sub_role'      => $user->sub_role,
                    'etablissement' => $user->etablissement,
                    'telephone'     => $user->telephone,
                    'photo'         => $photo,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $membres
        ]);
    }
}
