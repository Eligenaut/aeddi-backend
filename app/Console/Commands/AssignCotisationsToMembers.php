<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Cotisation;
use App\Models\CotisationMembre;
use Illuminate\Console\Command;

class AssignCotisationsToMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotisations:assign-to-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Associer tous les membres existants aux cotisations actives';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Association des cotisations actives aux membres...');

        // Récupérer tous les membres (sauf admin)
        $members = User::where('email', '!=', 'admin@aeddi.com')->get();
        $this->info("Nombre de membres trouvés: {$members->count()}");

        // Récupérer toutes les cotisations non terminées/annulées
        $activeCotisations = Cotisation::whereNotIn('statut', ['terminee', 'annulee'])->get();
        $this->info("Nombre de cotisations actives trouvées: {$activeCotisations->count()}");

        if ($activeCotisations->isEmpty()) {
            $this->warn('Aucune cotisation active trouvée.');
            return;
        }
        $totalAssigned = 0;

        foreach ($members as $member) {
            $this->info("Traitement du membre: {$member->name} ({$member->email})");
            
            foreach ($activeCotisations as $cotisation) {
                $existing = CotisationMembre::where('user_id', $member->id)
                                          ->where('cotisation_id', $cotisation->id)
                                          ->first();

                if (!$existing) {
                    CotisationMembre::create([
                        'user_id' => $member->id,
                        'cotisation_id' => $cotisation->id,
                        'statut' => 'non_paye',
                        'montant_restant' => $cotisation->montant
                    ]);
                    
                    $totalAssigned++;
                    $this->line("  ✓ Associé à la cotisation: {$cotisation->nom}");
                } else {
                    $this->line("  - Déjà associé à: {$cotisation->nom}");
                }
            }
        }

        $this->info("Terminé! {$totalAssigned} nouvelles associations créées.");
    }
}