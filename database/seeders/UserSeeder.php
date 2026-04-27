<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();
        $dar = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $arusha = Location::query()->where('code', 'SHOP-ARU')->firstOrFail();
        $mwanza = Location::query()->where('code', 'SHOP-MWA')->firstOrFail();

        $users = [
            [
                'role' => 'admin',
                'name' => 'Asha Mwakalonge',
                'username' => 'asha.admin',
                'email' => 'admin@inventory.tz',
                'phone' => '+255710000001',
                'location_id' => $warehouse->id,
            ],
            [
                'role' => 'warehouse_manager',
                'name' => 'John Msemwa',
                'username' => 'john.warehouse',
                'email' => 'warehouse.manager@inventory.tz',
                'phone' => '+255710000002',
                'location_id' => $warehouse->id,
            ],
            [
                'role' => 'warehouse_user',
                'name' => 'Neema Mollel',
                'username' => 'neema.ops',
                'email' => 'warehouse.user@inventory.tz',
                'phone' => '+255710000003',
                'location_id' => $warehouse->id,
            ],
            [
                'role' => 'shop_manager',
                'name' => 'Yusuph Kweka',
                'username' => 'yusuph.dar',
                'email' => 'dar.manager@inventory.tz',
                'phone' => '+255710000004',
                'location_id' => $dar->id,
            ],
            [
                'role' => 'shop_user',
                'name' => 'Faraja Mbise',
                'username' => 'faraja.arusha',
                'email' => 'arusha.ops@inventory.tz',
                'phone' => '+255710000005',
                'location_id' => $arusha->id,
            ],
            [
                'role' => 'retail_staff',
                'name' => 'Mariam Kessy',
                'username' => 'mariam.mwanza',
                'email' => 'mwanza.retail@inventory.tz',
                'phone' => '+255710000006',
                'location_id' => $mwanza->id,
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::query()->where('name', $userData['role'])->firstOrFail();

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'phone' => $userData['phone'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                    'default_location_id' => $userData['location_id'],
                    'status' => UserStatus::Active->value,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignedLocations()->syncWithoutDetaching([
                $userData['location_id'] => ['is_primary' => true],
            ]);
        }
    }
}
