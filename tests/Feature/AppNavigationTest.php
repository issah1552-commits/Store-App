<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Services\AppNavigationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_sales_links_are_hidden_when_a_warehouse_is_selected(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $warehouse = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();

        $titles = $this->navigationTitles(app(AppNavigationService::class)->forUser($admin, $warehouse));

        $this->assertNotContains('Orders', $titles);
        $this->assertNotContains('Invoices', $titles);
        $this->assertContains('Transfers', $titles);
    }

    public function test_sales_links_are_visible_when_a_shop_is_selected(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $shop = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();

        $titles = $this->navigationTitles(app(AppNavigationService::class)->forUser($admin, $shop));

        $this->assertContains('Orders', $titles);
        $this->assertContains('Invoices', $titles);
    }

    protected function navigationTitles(array $navigation): array
    {
        return collect($navigation)
            ->pluck('items')
            ->flatten(1)
            ->pluck('title')
            ->all();
    }
}
