<?php

namespace Tests\Feature\Inventory;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_color_instead_of_category(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();

        $this->actingAs($user)
            ->post(route('products.store'), [
                'brand_name' => 'Kitenge Prime',
                'color' => 'Sun Gold',
                'description' => 'Test product created from the simplified form.',
                'variants' => [
                    [
                        'meter_length' => 25,
                        'rolls' => 3,
                        'standard_cost_tzs' => 0,
                        'wholesale_price_tzs' => 0,
                        'retail_price_tzs' => 0,
                        'low_stock_threshold' => 2,
                    ],
                    [
                        'meter_length' => 50,
                        'rolls' => 2,
                        'standard_cost_tzs' => 0,
                        'wholesale_price_tzs' => 0,
                        'retail_price_tzs' => 0,
                        'low_stock_threshold' => 2,
                    ],
                ],
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('brand_name', 'Kitenge Prime')->firstOrFail();

        $this->assertTrue(Category::query()->where('slug', 'general')->exists());
        $this->assertSame(['Sun Gold'], $product->variants()->pluck('color')->unique()->values()->all());
        $this->assertSame(2, $product->variants()->count());
    }
}
