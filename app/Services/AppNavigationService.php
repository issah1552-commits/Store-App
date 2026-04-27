<?php

namespace App\Services;

use App\Models\User;

class AppNavigationService
{
    public function forUser(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                [
                    'title' => 'Operations',
                    'items' => [
                        ['title' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'layout-grid'],
                        ['title' => 'Orders', 'url' => '/orders', 'icon' => 'shopping-cart'],
                        ['title' => 'Products', 'url' => '/products', 'icon' => 'package-search'],
                        ['title' => 'Stores', 'url' => '/stores', 'icon' => 'map-pinned'],
                        ['title' => 'Transfers', 'url' => '/transfers', 'icon' => 'arrow-left-right'],
                        ['title' => 'Invoices', 'url' => '/invoices', 'icon' => 'receipt-text'],
                        ['title' => 'Reports', 'url' => '/reports', 'icon' => 'chart-column'],
                        ['title' => 'Users', 'url' => '/users', 'icon' => 'users'],
                    ],
                ],
            ];
        }

        if ($user->isWarehouseUser()) {
            return [
                [
                    'title' => 'Warehouse',
                    'items' => [
                        ['title' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'layout-grid'],
                        ['title' => 'Products', 'url' => '/products', 'icon' => 'package-search'],
                        ['title' => 'Stores', 'url' => '/stores', 'icon' => 'map-pinned'],
                        ['title' => 'Transfers', 'url' => '/transfers', 'icon' => 'arrow-left-right'],
                        ['title' => 'Reports', 'url' => '/reports', 'icon' => 'chart-column'],
                    ],
                ],
            ];
        }

        return [
            [
                'title' => 'Shop',
                'items' => [
                    ['title' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'layout-grid'],
                    ['title' => 'Orders', 'url' => '/orders', 'icon' => 'shopping-cart'],
                    ['title' => 'Products', 'url' => '/products', 'icon' => 'package-search'],
                    ['title' => 'Transfers', 'url' => '/transfers', 'icon' => 'arrow-left-right'],
                    ['title' => 'Invoices', 'url' => '/invoices', 'icon' => 'receipt-text'],
                    ['title' => 'Reports', 'url' => '/reports', 'icon' => 'chart-column'],
                ],
            ],
        ];
    }
}
