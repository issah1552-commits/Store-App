<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_warehouse_manager_can_authenticate_using_email_and_reach_dashboard(): void
    {
        $user = User::query()->where('email', 'warehouse.manager@inventory.tz')->firstOrFail();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk();
    }

    public function test_shop_manager_can_authenticate_using_username_and_reach_dashboard(): void
    {
        $user = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk();
    }
}
