<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app.currency' => ['value_type' => 'string', 'value' => 'TZS', 'description' => 'Default and only transaction currency'],
            'app.timezone' => ['value_type' => 'string', 'value' => 'Africa/Dar_es_Salaam', 'description' => 'System timezone'],
            'transfers.require_admin_approval' => ['value_type' => 'boolean', 'value' => true, 'description' => 'All warehouse to shop transfers require admin approval'],
            'inventory.allow_negative_stock' => ['value_type' => 'boolean', 'value' => false, 'description' => 'Negative stock is never allowed'],
            'internal_movements.default_threshold' => ['value_type' => 'integer', 'value' => 20, 'description' => 'Default per transaction threshold'],
        ];

        foreach ($settings as $key => $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value_type' => $setting['value_type'],
                    'value' => ['data' => $setting['value']],
                    'description' => $setting['description'],
                ],
            );
        }
    }
}
