<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version ?? '',
            ])
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_admin_can_filter_the_dashboard_by_location(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version ?? '',
            ])
            ->get('/dashboard?location_id='.$location->id)
            ->assertOk()
            ->assertJsonPath('props.filters.location_id', $location->id)
            ->assertJsonPath('props.metrics.cards.0.label', 'Products in Store');
    }

    public function test_shop_users_cannot_filter_the_dashboard_to_an_unauthorized_location(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'SHOP-ARU')->firstOrFail();
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version ?? '',
            ])
            ->get('/dashboard?location_id='.$location->id)
            ->assertForbidden();
    }
}
