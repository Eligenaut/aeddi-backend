<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Http\Request;

class AccueilController extends Controller
{
    /**
     * Retourne les activités publiques (sans authentification)
     */
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
                    'image'       => $activite->image
                                        ? asset('storage/' . $activite->image)
                                        : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $activites
        ]);
    }

    /**
     * Retourne les membres du bureau (sans authentification)
     */
    public function bureau()
    {
        $membres = User::where('role', 'bureau')
            ->orderBy('sub_role')
            ->get()
            ->map(function ($user) {
                // Priorité : avatar Google → profile_image uploadée → null
                $photo = $user->avatar
                    ?? ($user->profile_image ? asset('storage/' . $user->profile_image) : null);

                return [
                    'id'           => $user->id,
                    'nom'          => $user->nom,
                    'prenom'       => $user->prenom,
                    'name'         => $user->name,
                    'sub_role'     => $user->sub_role,
                    'etablissement'=> $user->etablissement,
                    'telephone'    => $user->telephone,
                    'photo'        => $photo,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $membres
        ]);
    }
}