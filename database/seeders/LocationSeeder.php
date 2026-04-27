<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Dodoma Central Warehouse',
                'code' => 'WAREHOUSE-DOD',
                'region_name' => 'Dodoma',
                'type' => LocationType::Warehouse->value,
                'is_active' => true,
            ],
            [
                'name' => 'Dar es Salaam',
                'code' => 'SHOP-DAR',
                'region_name' => 'Dar es Salaam',
                'type' => LocationType::Shop->value,
                'is_active' => true,
            ],
            [
                'name' => 'Arusha',
                'code' => 'SHOP-ARU',
                'region_name' => 'Arusha',
                'type' => LocationType::Shop->value,
                'is_active' => true,
            ],
            [
                'name' => 'Mwanza',
                'code' => 'SHOP-MWA',
                'region_name' => 'Mwanza',
                'type' => LocationType::Shop->value,
                'is_active' => true,
            ],
            [
                'name' => 'Mbeya',
                'code' => 'SHOP-MBE',
                'region_name' => 'Mbeya',
                'type' => LocationType::Shop->value,
                'is_active' => false,
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(['code' => $location['code']], $location);
        }
    }
}
