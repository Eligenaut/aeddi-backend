<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RolePermission;
use App\Helpers\Permissions;

class RolePermissionSeeder extends Seeder
{
    /**
     * Peuple la table role_permissions avec les permissions de base par rôle.
     * Source unique des permissions : Permissions::DEFAULTS.
     * Aucun fallback côté code : tout passe par cette table.
     */
    public function run(): void
    {
        $defaults = Permissions::DEFAULTS;

        $subRoles = [
            'PRESIDENT',
            'VICE_PRESIDENT',
            'TRESORIER',
            'VICE_TRESORIER',
            'COMMISSAIRE_COMPTE',
            'COMMISSION_CERCLE_ETUDE',
            'COMMISSION_INFORMATIQUE',
            'COMMISSION_LOGEMENT',
            'COMMISSION_SOCIAL',
            'COMMISSION_FETE',
            'COMMISSION_SPORT',
            'COMMISSION_COMMUNICATION',
            'COMMISSION_ENVIRONNEMENT',
        ];

        foreach ($defaults as $role => $permissions) {
            RolePermission::updateOrCreate(
                ['role' => $role, 'sub_role' => null],
                ['permissions' => $permissions]
            );
        }

        // Chaque sous-rôle BUREAU hérite des permissions BUREAU (modifiables ensuite)
        // Sauf PRESIDENT qui a toutes les permissions (comme l'admin)
        foreach ($subRoles as $subRole) {
            $permissions = $subRole === 'PRESIDENT' ? Permissions::ALL : $defaults['BUREAU'];
            RolePermission::updateOrCreate(
                ['role' => 'BUREAU', 'sub_role' => $subRole],
                ['permissions' => $permissions]
            );
        }
    }
}
