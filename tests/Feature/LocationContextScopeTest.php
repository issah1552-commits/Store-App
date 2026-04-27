<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationContextScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_dashboard_location_scopes_orders_invoices_and_reports(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $dar = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $version = app(HandleInertiaRequests::class)->version(request());
        $headers = [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version ?? '',
        ];

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get('/dashboard?location_id='.$dar->id)
            ->assertOk();

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get('/orders')
            ->assertOk()
            ->assertJsonCount(1, 'props.orders.data')
            ->assertJsonPath('props.orders.data.0.location.name', 'Dar es Salaam');

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get('/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'props.invoices.data')
            ->assertJsonPath('props.invoices.data.0.location.name', 'Dar es Salaam');

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get('/reports?type=orders')
            ->assertOk()
            ->assertJsonCount(1, 'props.dataset.data')
            ->assertJsonPath('props.dataset.data.0.location.name', 'Dar es Salaam');
    }
}
