<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('email', 'warehouse.manager@inventory.tz')->firstOrFail();

        $products = [
            [
                'brand_name' => 'Kilimanjaro Print Deluxe',
                'category' => 'Printed Fabric Rolls',
                'description' => 'Premium printed fabric rolls for wholesale and retail shops.',
                'variants' => [
                    ['color' => 'Royal Blue', 'meter_length' => 50, 'retail_price_tzs' => 480000, 'wholesale_price_tzs' => 430000, 'standard_cost_tzs' => 360000, 'low_stock_threshold' => 8],
                    ['color' => 'Crimson Red', 'meter_length' => 50, 'retail_price_tzs' => 480000, 'wholesale_price_tzs' => 430000, 'standard_cost_tzs' => 360000, 'low_stock_threshold' => 8],
                    ['color' => 'Emerald Green', 'meter_length' => 70, 'retail_price_tzs' => 650000, 'wholesale_price_tzs' => 590000, 'standard_cost_tzs' => 500000, 'low_stock_threshold' => 6],
                ],
            ],
            [
                'brand_name' => 'Serengeti Canvas Pro',
                'category' => 'PVC Canvas Rolls',
                'description' => 'Heavy duty canvas rolls for commercial use.',
                'variants' => [
                    ['color' => 'Ocean Blue', 'meter_length' => 30, 'retail_price_tzs' => 520000, 'wholesale_price_tzs' => 470000, 'standard_cost_tzs' => 395000, 'low_stock_threshold' => 5],
                    ['color' => 'Ash Grey', 'meter_length' => 45, 'retail_price_tzs' => 710000, 'wholesale_price_tzs' => 650000, 'standard_cost_tzs' => 560000, 'low_stock_threshold' => 4],
                ],
            ],
            [
                'brand_name' => 'Savanna Shade Shield',
                'category' => 'Shade Net Rolls',
                'description' => 'Durable shade net rolls for agriculture and commercial coverings.',
                'variants' => [
                    ['color' => 'Forest Green', 'meter_length' => 100, 'retail_price_tzs' => 850000, 'wholesale_price_tzs' => 790000, 'standard_cost_tzs' => 690000, 'low_stock_threshold' => 5],
                    ['color' => 'Black', 'meter_length' => 100, 'retail_price_tzs' => 850000, 'wholesale_price_tzs' => 790000, 'standard_cost_tzs' => 690000, 'low_stock_threshold' => 5],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::query()->where('name', $productData['category'])->firstOrFail();

            $product = Product::updateOrCreate(
                ['brand_name' => $productData['brand_name']],
                [
                    'category_id' => $category->id,
                    'description' => $productData['description'],
                    'is_active' => true,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ],
            );

            foreach ($productData['variants'] as $variantData) {
                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'color' => $variantData['color'],
                        'meter_length' => $variantData['meter_length'],
                    ],
                    [
                        'sku' => strtoupper(Str::slug($product->brand_name.'-'.$variantData['color'].'-'.$variantData['meter_length'], '-')),
                        'standard_cost_tzs' => $variantData['standard_cost_tzs'],
                        'wholesale_price_tzs' => $variantData['wholesale_price_tzs'],
                        'retail_price_tzs' => $variantData['retail_price_tzs'],
                        'low_stock_threshold' => $variantData['low_stock_threshold'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
