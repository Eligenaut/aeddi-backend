<?php

namespace Database\Seeders;

use App\Models\TypeLogement;
use App\Models\OptionCampus;
use App\Models\SectionCampus;
use App\Models\BlocCampus;
use Illuminate\Database\Seeder;

class LogementSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Campus ──────────────────────────────────────────────
        $campus = TypeLogement::create(['nom' => 'Campus']);

        // Bloc
        $bloc = OptionCampus::create(['type_logement_id' => $campus->id, 'nom' => 'Bloc']);
        foreach (['A', 'B'] as $lettre) {
            $section = SectionCampus::create(['option_campus_id' => $bloc->id, 'nom' => "Bloc $lettre"]);
            foreach (range(1, 4) as $num) {
                BlocCampus::create(['section_campus_id' => $section->id, 'nom' => "$lettre$num"]);
            }
        }

        // PJ
        $pj = OptionCampus::create(['type_logement_id' => $campus->id, 'nom' => 'PJ']);
        $sectionPj = SectionCampus::create(['option_campus_id' => $pj->id, 'nom' => 'PJ']);
        foreach (range(1, 4) as $num) {
            BlocCampus::create(['section_campus_id' => $sectionPj->id, 'nom' => "PJ-$num"]);
        }

        // BR
        $br = OptionCampus::create(['type_logement_id' => $campus->id, 'nom' => 'BR']);
        $sectionBr = SectionCampus::create(['option_campus_id' => $br->id, 'nom' => 'BR']);
        foreach (range(1, 4) as $num) {
            BlocCampus::create(['section_campus_id' => $sectionBr->id, 'nom' => str_pad($num, 3, '0', STR_PAD_LEFT)]);
        }

        // BM
        $bm = OptionCampus::create(['type_logement_id' => $campus->id, 'nom' => 'BM']);
        $sectionBm = SectionCampus::create(['option_campus_id' => $bm->id, 'nom' => 'BM']);
        foreach (range(1, 4) as $num) {
            BlocCampus::create(['section_campus_id' => $sectionBm->id, 'nom' => "BM-$num"]);
        }

        // ─── Ville ───────────────────────────────────────────────
        TypeLogement::create(['nom' => 'Ville']);
    }
}
