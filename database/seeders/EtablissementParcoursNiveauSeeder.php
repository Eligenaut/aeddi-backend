<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Parcours;
use App\Models\Niveau;
use Illuminate\Database\Seeder;

class EtablissementParcoursNiveauSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ESP - École Supérieure Polytechnique d'Antsiranana
        $esp = Etablissement::create(['nom' => 'ESP - École Supérieure Polytechnique d\'Antsiranana']);
        $this->createParcoursAvecNiveaux($esp, [
            'Génie Électrique',
            'Électronique et Informatique Industrielles',
            'Génie Mécanique',
            'Hydraulique Énergétique',
            'Génie Civil',
        ]);

        // 2. ENSET - École Normale Supérieure pour l'Enseignement Technique
        $enset = Etablissement::create(['nom' => 'ENSET - École Normale Supérieure pour l\'Enseignement Technique']);
        $this->createParcoursAvecNiveaux($enset, [
            'Professorat Technique en Génie Électrique',
            'Professorat Technique en Génie Mécanique',
            'Professorat Technique en Génie Mathématique et Informatique',
        ]);

        // 3. FS - Faculté des Sciences
        $fs = Etablissement::create(['nom' => 'FS - Faculté des Sciences']);
        $this->createParcoursAvecNiveaux($fs, [
            'Science Physique',
            'Chimie',
            'Science de la Nature et de l\'Environnement',
        ]);

        // 4. FLSH - Faculté des Lettres et des Sciences Humaines
        $flsh = Etablissement::create(['nom' => 'FLSH - Faculté des Lettres et des Sciences Humaines']);
        $this->createParcoursAvecNiveaux($flsh, [
            'Lettres Françaises',
            'Lettres Anglo-Américaines',
        ]);

        // 5. DEGSP - Faculté de Droit, Économie, Gestion et Science Politique
        $degsp = Etablissement::create(['nom' => 'DEGSP - Faculté de Droit, Économie, Gestion et Science Politique']);
        $this->createParcoursAvecNiveaux($degsp, [
            'Droit',
            'Économie',
            'Gestion',
            'Science Politique',
        ]);

        // 6. FM - Faculté de Médecine
        $fm = Etablissement::create(['nom' => 'FM - Faculté de Médecine']);
        $this->createParcoursAvecNiveauxMedecine($fm, [
            'Médecin Généraliste',
            'Infirmier',
            'Paramède',
        ]);

        // 7. ESAED - École Supérieure en Agronomie et Environnement de Diego
        $esaed = Etablissement::create(['nom' => 'ESAED - École Supérieure en Agronomie et Environnement de Diego']);
        $this->createParcoursAvecNiveaux($esaed, [
            'Agronomie',
            'Environnement',
        ]);

        // 8. ISAE - Institut Supérieur en Administration d'Entreprises
        $isae = Etablissement::create(['nom' => 'ISAE - Institut Supérieur en Administration d\'Entreprises']);
        $this->createParcoursAvecNiveaux($isae, [
            'Assistanat de Direction',
            'Techniques Bancaires',
        ]);
    }

    private function createParcoursAvecNiveaux(Etablissement $etablissement, array $parcoursNoms): void
    {
        $niveaux = ['L1', 'L2', 'L3'];
        foreach ($parcoursNoms as $nom) {
            $parcours = Parcours::create([
                'etablissement_id' => $etablissement->id,
                'nom' => $nom,
            ]);
            foreach ($niveaux as $niveauNom) {
                Niveau::create([
                    'parcours_id' => $parcours->id,
                    'nom' => $niveauNom,
                ]);
            }
        }
    }

    private function createParcoursAvecNiveauxMedecine(Etablissement $etablissement, array $parcoursNoms): void
    {
        $niveaux = ['DCEM1', 'DCEM2', 'DCEM3', 'DCEM4'];
        foreach ($parcoursNoms as $nom) {
            $parcours = Parcours::create([
                'etablissement_id' => $etablissement->id,
                'nom' => $nom,
            ]);
            foreach ($niveaux as $niveauNom) {
                Niveau::create([
                    'parcours_id' => $parcours->id,
                    'nom' => $niveauNom,
                ]);
            }
        }
    }
}
