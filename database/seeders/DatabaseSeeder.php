<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            InternalMovementRuleSeeder::class,
            StockSeeder::class,
            TransferSeeder::class,
            OrderInvoiceSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
