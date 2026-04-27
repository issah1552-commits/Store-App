<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InternalMovementRule;
use App\Models\Location;
use App\Models\Role;
use Illuminate\Database\Seeder;

class InternalMovementRuleSeeder extends Seeder
{
    public function run(): void
    {
        $shopManagerRole = Role::query()->where('name', 'shop_manager')->firstOrFail();
        $retailRole = Role::query()->where('name', 'retail_staff')->firstOrFail();
        $fabricCategory = Category::query()->where('slug', 'printed-fabric-rolls')->firstOrFail();

        foreach (Location::query()->where('type', 'shop')->where('is_active', true)->get() as $shop) {
            InternalMovementRule::updateOrCreate(
                [
                    'location_id' => $shop->id,
                    'role_id' => $shopManagerRole->id,
                    'category_id' => null,
                ],
                [
                    'max_quantity_per_item' => 20,
                    'max_quantity_per_transaction' => 40,
                    'max_daily_quantity_per_user' => 80,
                    'after_hours_blocked' => false,
                    'requires_reason' => true,
                    'is_active' => true,
                ],
            );

            InternalMovementRule::updateOrCreate(
                [
                    'location_id' => $shop->id,
                    'role_id' => $retailRole->id,
                    'category_id' => $fabricCategory->id,
                ],
                [
                    'max_quantity_per_item' => 8,
                    'max_quantity_per_transaction' => 15,
                    'max_daily_quantity_per_user' => 25,
                    'after_hours_blocked' => true,
                    'after_hours_start' => '19:00:00',
                    'after_hours_end' => '06:00:00',
                    'requires_reason' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
