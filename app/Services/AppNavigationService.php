<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\User;

class AppNavigationService
{
    public function forUser(User $user, ?Location $selectedLocation = null): array
    {
        $hideSalesLinks = $this->shouldHideSalesLinks($user, $selectedLocation);

        if ($user->isAdmin()) {
            return [
                [
                    'title' => 'Operations',
                    'items' => $this->filterSalesLinks([
                        ['title' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'layout-grid'],
                        ['title' => 'Orders', 'url' => '/orders', 'icon' => 'shopping-cart'],
                        ['title' => 'Products', 'url' => '/products', 'icon' => 'package-search'],
                        ['title' => 'Stores', 'url' => '/stores', 'icon' => 'map-pinned'],
                        ['title' => 'Transfers', 'url' => '/transfers', 'icon' => 'arrow-left-right'],
                        ['title' => 'Invoices', 'url' => '/invoices', 'icon' => 'receipt-text'],
                        ['title' => 'Reports', 'url' => '/reports', 'icon' => 'chart-column'],
                        ['title' => 'Users', 'url' => '/users', 'icon' => 'users'],
                    ], $hideSalesLinks),
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
                'items' => $this->filterSalesLinks([
                    ['title' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'layout-grid'],
                    ['title' => 'Orders', 'url' => '/orders', 'icon' => 'shopping-cart'],
                    ['title' => 'Products', 'url' => '/products', 'icon' => 'package-search'],
                    ['title' => 'Transfers', 'url' => '/transfers', 'icon' => 'arrow-left-right'],
                    ['title' => 'Invoices', 'url' => '/invoices', 'icon' => 'receipt-text'],
                    ['title' => 'Reports', 'url' => '/reports', 'icon' => 'chart-column'],
                ], $hideSalesLinks),
            ],
        ];
    }

    protected function shouldHideSalesLinks(User $user, ?Location $selectedLocation): bool
    {
        if ($selectedLocation) {
            return $selectedLocation->type === LocationType::Warehouse;
        }

        return ! $user->isAdmin() && $this->hasWarehouseAssignment($user);
    }

    protected function hasWarehouseAssignment(User $user): bool
    {
        if ($user->isWarehouseUser()) {
            return true;
        }

        $defaultLocation = $user->relationLoaded('defaultLocation')
            ? $user->defaultLocation
            : $user->defaultLocation()->first(['id', 'type']);

        if ($defaultLocation?->type === LocationType::Warehouse) {
            return true;
        }

        if ($user->relationLoaded('assignedLocations')) {
            return $user->assignedLocations->contains(fn (Location $location) => $location->type === LocationType::Warehouse);
        }

        return $user->assignedLocations()
            ->where('type', LocationType::Warehouse->value)
            ->exists();
    }

    protected function filterSalesLinks(array $items, bool $hideSalesLinks): array
    {
        if (! $hideSalesLinks) {
            return $items;
        }

        return array_values(array_filter(
            $items,
            fn (array $item) => ! in_array($item['title'], ['Orders', 'Invoices'], true),
        ));
    }
}
