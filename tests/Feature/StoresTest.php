<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_store(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();

        $this->actingAs($user)
            ->post(route('stores.store'), [
                'name' => 'Tanga',
                'code' => 'shop-tanga',
                'type' => 'shop',
                'region_name' => 'Tanga',
                'is_active' => true,
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('locations', [
            'name' => 'Tanga',
            'code' => 'SHOP-TANGA',
            'type' => 'shop',
            'region_name' => 'Tanga',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_a_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();

        $this->actingAs($user)
            ->post(route('stores.store'), [
                'name' => 'Zanzibar Warehouse',
                'code' => 'warehouse-znz',
                'type' => 'warehouse',
                'region_name' => 'Zanzibar',
                'is_active' => true,
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('locations', [
            'name' => 'Zanzibar Warehouse',
            'code' => 'WAREHOUSE-ZNZ',
            'type' => 'warehouse',
            'region_name' => 'Zanzibar',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_shut_down_a_store(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();

        $this->actingAs($user)
            ->post(route('stores.toggle-active', $location))
            ->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'is_active' => false,
        ]);
    }

    public function test_store_shutdown_action_does_not_apply_to_warehouses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();

        $this->actingAs($user)
            ->post(route('stores.toggle-active', $location))
            ->assertNotFound();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'is_active' => true,
        ]);
    }
}
