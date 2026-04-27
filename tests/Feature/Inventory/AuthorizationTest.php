<?php

namespace Tests\Feature\Inventory;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_shop_users_can_not_access_product_creation()
    {
        $shopManager = \App\Models\User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();

        $this->actingAs($shopManager)
            ->getJson(route('products.create'))
            ->assertForbidden();
    }

    public function test_warehouse_users_can_not_access_orders_module()
    {
        $warehouseUser = \App\Models\User::query()->where('email', 'warehouse.user@inventory.tz')->firstOrFail();

        $this->actingAs($warehouseUser)
            ->getJson(route('orders.index'))
            ->assertForbidden();
    }
}
