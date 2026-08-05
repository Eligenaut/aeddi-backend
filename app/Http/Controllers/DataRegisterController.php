<?php

namespace App\Http\Controllers;

use App\Models\Etablissement;
use App\Models\Parcours;
use App\Models\Niveau;
use App\Models\Promotion;
use App\Models\TypeLogement;
use App\Models\OptionCampus;
use App\Models\SectionCampus;
use App\Models\BlocCampus;
use App\Models\Quartier;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataRegisterController extends Controller
{
    public function getAll()
    {
        $etablissements = Etablissement::with(['parcours.niveaux'])->orderBy('nom')->get();
        $parcours = Parcours::with(['etablissement', 'niveaux'])->orderBy('nom')->get();
        $niveaux = Niveau::with(['parcours.etablissement'])->orderBy('nom')->get();
        $promotions = Promotion::orderBy('annee', 'desc')->get();
        $typesLogement = TypeLogement::with(['optionsCampus.sections.blocs'])->orderBy('nom')->get();
        $optionsCampus = OptionCampus::with(['typeLogement', 'sections'])->orderBy('nom')->get();
        $sectionsCampus = SectionCampus::with(['optionCampus.typeLogement', 'blocs'])->orderBy('nom')->get();
        $blocsCampus = BlocCampus::with(['sectionCampus.optionCampus.typeLogement'])->orderBy('nom')->get();
        $quartiers = Quartier::orderBy('nom')->get();
        $villes = Ville::with('quartiers')->orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'etablissements' => $etablissements,
                'parcours' => $parcours,
                'niveaux' => $niveaux,
                'promotions' => $promotions,
                'types_logement' => $typesLogement,
                'options_campus' => $optionsCampus,
                'sections_campus' => $sectionsCampus,
                'blocs_campus' => $blocsCampus,
                'quartiers' => $quartiers,
                'villes' => $villes,
            ],
        ]);
    }

    // ─── Établissements ────────────────────────────────────────

    public function getEtablissements()
    {
        return response()->json([
            'success' => true,
            'data' => Etablissement::with('parcours.niveaux')->orderBy('nom')->get(),
        ]);
    }

    public function storeEtablissement(Request $request)
    {
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $etablissement = Etablissement::create($validated);
        return response()->json(['success' => true, 'data' => $etablissement], 201);
    }

    public function updateEtablissement(Request $request, $id)
    {
        $etablissement = Etablissement::findOrFail($id);
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $etablissement->update($validated);
        return response()->json(['success' => true, 'data' => $etablissement]);
    }

    public function deleteEtablissement($id)
    {
        Etablissement::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Parcours ──────────────────────────────────────────────

    public function storeParcours(Request $request)
    {
        $validated = $request->validate([
            'etablissement_id' => 'required|exists:etablissements,id',
            'nom' => 'required|string|max:255',
        ]);
        $parcours = Parcours::create($validated);
        return response()->json(['success' => true, 'data' => $parcours], 201);
    }

    public function updateParcours(Request $request, $id)
    {
        $parcours = Parcours::findOrFail($id);
        $validated = $request->validate([
            'etablissement_id' => 'required|exists:etablissements,id',
            'nom' => 'required|string|max:255',
        ]);
        $parcours->update($validated);
        return response()->json(['success' => true, 'data' => $parcours]);
    }

    public function deleteParcours($id)
    {
        Parcours::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Niveaux ───────────────────────────────────────────────

    public function storeNiveau(Request $request)
    {
        $validated = $request->validate([
            'parcours_id' => 'required|exists:parcours,id',
            'nom' => 'required|string|max:255',
        ]);
        $niveau = Niveau::create($validated);
        return response()->json(['success' => true, 'data' => $niveau], 201);
    }

    public function updateNiveau(Request $request, $id)
    {
        $niveau = Niveau::findOrFail($id);
        $validated = $request->validate([
            'parcours_id' => 'required|exists:parcours,id',
            'nom' => 'required|string|max:255',
        ]);
        $niveau->update($validated);
        return response()->json(['success' => true, 'data' => $niveau]);
    }

    public function deleteNiveau($id)
    {
        Niveau::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Promotions ────────────────────────────────────────────

    public function getPromotions()
    {
        return response()->json([
            'success' => true,
            'data' => Promotion::orderBy('annee', 'desc')->get(),
        ]);
    }

    public function storePromotion(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'annee' => 'required|integer|unique:promotions,annee',
        ]);
        $promotion = Promotion::create($validated);
        return response()->json(['success' => true, 'data' => $promotion], 201);
    }

    public function updatePromotion(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'annee' => 'required|integer|unique:promotions,annee,' . $id,
        ]);
        $promotion->update($validated);
        return response()->json(['success' => true, 'data' => $promotion]);
    }

    public function deletePromotion($id)
    {
        Promotion::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Types de Logement ─────────────────────────────────────

    public function getTypesLogement()
    {
        return response()->json([
            'success' => true,
            'data' => TypeLogement::with('optionsCampus.sections.blocs')->orderBy('nom')->get(),
        ]);
    }

    public function storeTypeLogement(Request $request)
    {
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $type = TypeLogement::create($validated);
        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function updateTypeLogement(Request $request, $id)
    {
        $type = TypeLogement::findOrFail($id);
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $type->update($validated);
        return response()->json(['success' => true, 'data' => $type]);
    }

    public function deleteTypeLogement($id)
    {
        TypeLogement::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Options Campus ────────────────────────────────────────

    public function storeOptionCampus(Request $request)
    {
        $validated = $request->validate([
            'type_logement_id' => 'required|exists:types_logement,id',
            'nom' => 'required|string|max:255',
        ]);
        $option = OptionCampus::create($validated);
        return response()->json(['success' => true, 'data' => $option], 201);
    }

    public function updateOptionCampus(Request $request, $id)
    {
        $option = OptionCampus::findOrFail($id);
        $validated = $request->validate([
            'type_logement_id' => 'required|exists:types_logement,id',
            'nom' => 'required|string|max:255',
        ]);
        $option->update($validated);
        return response()->json(['success' => true, 'data' => $option]);
    }

    public function deleteOptionCampus($id)
    {
        OptionCampus::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Sections Campus ───────────────────────────────────────

    public function storeSectionCampus(Request $request)
    {
        $validated = $request->validate([
            'option_campus_id' => 'required|exists:options_campus,id',
            'nom' => 'required|string|max:255',
        ]);
        $section = SectionCampus::create($validated);
        return response()->json(['success' => true, 'data' => $section], 201);
    }

    public function updateSectionCampus(Request $request, $id)
    {
        $section = SectionCampus::findOrFail($id);
        $validated = $request->validate([
            'option_campus_id' => 'required|exists:options_campus,id',
            'nom' => 'required|string|max:255',
        ]);
        $section->update($validated);
        return response()->json(['success' => true, 'data' => $section]);
    }

    public function deleteSectionCampus($id)
    {
        SectionCampus::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Blocs Campus ──────────────────────────────────────────

    public function storeBlocCampus(Request $request)
    {
        $validated = $request->validate([
            'section_campus_id' => 'required|exists:sections_campus,id',
            'nom' => 'required|string|max:255',
        ]);
        $bloc = BlocCampus::create($validated);
        return response()->json(['success' => true, 'data' => $bloc], 201);
    }

    public function updateBlocCampus(Request $request, $id)
    {
        $bloc = BlocCampus::findOrFail($id);
        $validated = $request->validate([
            'section_campus_id' => 'required|exists:sections_campus,id',
            'nom' => 'required|string|max:255',
        ]);
        $bloc->update($validated);
        return response()->json(['success' => true, 'data' => $bloc]);
    }

    public function deleteBlocCampus($id)
    {
        BlocCampus::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Quartiers ─────────────────────────────────────────────

    public function getQuartiers()
    {
        return response()->json([
            'success' => true,
            'data' => Quartier::with('ville')->orderBy('nom')->get(),
        ]);
    }

    public function storeQuartier(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ville_id' => 'nullable|exists:villes,id',
        ]);
        $quartier = Quartier::create($validated);
        return response()->json(['success' => true, 'data' => $quartier], 201);
    }

    public function updateQuartier(Request $request, $id)
    {
        $quartier = Quartier::findOrFail($id);
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ville_id' => 'nullable|exists:villes,id',
        ]);
        $quartier->update($validated);
        return response()->json(['success' => true, 'data' => $quartier]);
    }

    public function deleteQuartier($id)
    {
        Quartier::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    // ─── Villes ────────────────────────────────────────────────

    public function getVilles()
    {
        return response()->json([
            'success' => true,
            'data' => Ville::with('quartiers')->orderBy('nom')->get(),
        ]);
    }

    public function storeVille(Request $request)
    {
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $ville = Ville::create($validated);
        return response()->json(['success' => true, 'data' => $ville], 201);
    }

    public function updateVille(Request $request, $id)
    {
        $ville = Ville::findOrFail($id);
        $validated = $request->validate(['nom' => 'required|string|max:255']);
        $ville->update($validated);
        return response()->json(['success' => true, 'data' => $ville]);
    }

    public function deleteVille($id)
    {
        Ville::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }
}
