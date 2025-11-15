<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportController extends Controller
{
    /**
     * Export des utilisateurs en CSV
     */
    public function exportUsers(Request $request)
    {
        // Vérifier les permissions
        $user = $request->user();
        if (!$this->canExportUsers($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour exporter les données'
            ], 403);
        }

        // Récupérer tous les utilisateurs avec leurs informations complètes
        $users = User::select([
            'id',
            'name',
            'nom',
            'prenom',
            'email',
            'role',
            'sub_role',
            'etablissement',
            'parcours',
            'niveau',
            'promotion',
            'logement',
            'bloc_campus',
            'quartier',
            'telephone',
            'profile_image',
            'email_verified_at',
            'created_at',
            'updated_at'
        ])->where('email', '!=', 'admin@aeddi.com')->get();

        // Préparer les données pour l'export
        $csvData = [];
        
        // En-têtes
        $csvData[] = [
            'ID',
            'Nom complet',
            'Nom',
            'Prénom',
            'Email',
            'Rôle',
            'Sous-rôle',
            'Établissement',
            'Parcours',
            'Niveau',
            'Promotion',
            'Type de logement',
            'Bloc campus',
            'Quartier',
            'Téléphone',
            'Image de profil',
            'Statut',
            'Date de création',
            'Dernière modification'
        ];

        // Données des utilisateurs
        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->name,
                $user->nom,
                $user->prenom,
                $user->email,
                $this->getRoleLabel($user->role),
                $this->getSubRoleLabel($user->sub_role),
                $user->etablissement,
                $user->parcours,
                $user->niveau,
                $user->promotion,
                $this->getLogementLabel($user->logement),
                $user->bloc_campus,
                $user->quartier,
                $user->telephone,
                $user->profile_image ? 'Oui' : 'Non',
                $user->email_verified_at ? 'Actif' : 'En attente',
                $user->created_at->format('d/m/Y H:i:s'),
                $user->updated_at->format('d/m/Y H:i:s')
            ];
        }

        // Générer le CSV
        $filename = 'utilisateurs_aeddi_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // Ajouter BOM pour UTF-8 (pour Excel)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($csvData as $row) {
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Export des utilisateurs en XLSX avec formatage
     */
    public function exportUsersXlsx(Request $request)
    {
        // Vérifier les permissions
        $user = $request->user();
        if (!$this->canExportUsers($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour exporter les données'
            ], 403);
        }

        // Récupérer tous les utilisateurs avec leurs informations complètes
        $users = User::select([
            'id',
            'name',
            'nom',
            'prenom',
            'email',
            'role',
            'sub_role',
            'etablissement',
            'parcours',
            'niveau',
            'promotion',
            'logement',
            'bloc_campus',
            'quartier',
            'telephone',
            'profile_image',
            'email_verified_at',
            'created_at',
            'updated_at'
        ])->where('email', '!=', 'admin@aeddi.com')->get();

        // Préparer les données pour l'export
        $data = [];
        
        // Données des utilisateurs
        foreach ($users as $user) {
            $data[] = [
                $user->id,
                $user->name,
                $user->nom,
                $user->prenom,
                $user->email,
                $this->getRoleLabel($user->role),
                $this->getSubRoleLabel($user->sub_role),
                $user->etablissement,
                $user->parcours,
                $user->niveau,
                $user->promotion,
                $this->getLogementLabel($user->logement),
                $user->bloc_campus,
                $user->quartier,
                $user->telephone ? "'" . $user->telephone : '', // Préfixe ' pour forcer le format texte
                $user->profile_image ? 'Oui' : 'Non',
                $user->email_verified_at ? 'Actif' : 'En attente',
                $user->created_at->format('d/m/Y H:i:s'),
                $user->updated_at->format('d/m/Y H:i:s')
            ];
        }

        $filename = 'utilisateurs_aeddi_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($data) implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'ID',
                    'Nom complet',
                    'Nom',
                    'Prénom',
                    'Email',
                    'Rôle',
                    'Sous-rôle',
                    'Établissement',
                    'Parcours',
                    'Niveau',
                    'Promotion',
                    'Type de logement',
                    'Bloc campus',
                    'Quartier',
                    'Téléphone',
                    'Image de profil',
                    'Statut',
                    'Date de création',
                    'Dernière modification'
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    // Style pour l'en-tête
                    1 => [
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'size' => 12
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '2E7D32'] // Vert foncé
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ]
                    ]
                ];
            }

            public function columnWidths(): array
            {
                return [
                    'A' => 8,   // ID
                    'B' => 25,  // Nom complet
                    'C' => 20,  // Nom
                    'D' => 20,  // Prénom
                    'E' => 30,  // Email
                    'F' => 18,  // Rôle
                    'G' => 25,  // Sous-rôle
                    'H' => 25,  // Établissement
                    'I' => 25,  // Parcours
                    'J' => 12,  // Niveau
                    'K' => 12,  // Promotion
                    'L' => 20,  // Type de logement
                    'M' => 18,  // Bloc campus
                    'N' => 18,  // Quartier
                    'O' => 18,  // Téléphone
                    'P' => 18,  // Image de profil
                    'Q' => 12,  // Statut
                    'R' => 22,  // Date de création
                    'S' => 22,  // Dernière modification
                ];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function(AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        
                        // Appliquer les bordures à toutes les cellules
                        $highestRow = $sheet->getHighestRow();
                        $highestColumn = $sheet->getHighestColumn();
                        
                        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000']
                                ]
                            ]
                        ]);

                        // Alterner les couleurs des lignes
                        for ($row = 2; $row <= $highestRow; $row++) {
                            $fillColor = $row % 2 == 0 ? 'F8F9FA' : 'FFFFFF';
                            $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $fillColor]
                                ]
                            ]);
                        }

                        // Centrer le contenu des cellules
                        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        // Formater la colonne téléphone comme du texte
                        $sheet->getStyle('O2:O' . $highestRow)->getNumberFormat()->setFormatCode('@');
                        
                        // Aligner à gauche la colonne téléphone pour une meilleure lisibilité
                        $sheet->getStyle('O2:O' . $highestRow)->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        // Geler la première ligne
                        $sheet->freezePane('A2');
                    }
                ];
            }
        }, $filename);
    }

    /**
     * Vérifier si l'utilisateur peut exporter les données
     */
    private function canExportUsers($user)
    {
        if (!$user) return false;
        
        // Admin peut toujours exporter
        if ($user->role === 'admin') return true;
        
        // Membres de bureau avec rôles spécifiques
        if ($user->role === 'bureau') {
            $allowedSubRoles = [
                'president',
                'vice_president',
                'tresorier',
                'vice_tresorier',
                'commissaire_compte'
            ];
            
            return in_array($user->sub_role, $allowedSubRoles);
        }
        
        return false;
    }

    /**
     * Obtenir le label du rôle
     */
    private function getRoleLabel($role)
    {
        $labels = [
            'admin' => 'Administrateur',
            'bureau' => 'Membre de bureau',
            'member' => 'Membre'
        ];
        
        return $labels[$role] ?? $role;
    }

    /**
     * Obtenir le label du sous-rôle
     */
    private function getSubRoleLabel($subRole)
    {
        if (!$subRole) return '';
        
        $labels = [
            'president' => 'Président',
            'vice_president' => 'Vice Président',
            'tresorier' => 'Trésorier(e)',
            'vice_tresorier' => 'Vice Trésorier',
            'commissaire_compte' => 'Commissaire au compte',
            'commission_cercle_etude' => 'Commission (Cercle d\'étude)',
            'commission_informatique' => 'Commission (Informatique)',
            'commission_logement' => 'Commission (Logement)',
            'commission_social' => 'Commission (Social)',
            'commission_fete' => 'Commission (Fête)',
            'commission_sport' => 'Commission (Sport)',
            'commission_communication' => 'Commission (Communication)',
            'commission_environnement' => 'Commission (Environnement)'
        ];
        
        return $labels[$subRole] ?? $subRole;
    }

    /**
     * Obtenir le label du type de logement
     */
    private function getLogementLabel($logement)
    {
        $labels = [
            'campus' => 'Campus',
            'ville' => 'Ville'
        ];
        
        return $labels[$logement] ?? $logement;
    }
}