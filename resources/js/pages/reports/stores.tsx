import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stores', href: '/stores' }];

export default function StoresIndex({
    locations,
    canCreateStores = false,
    canManageStores = false,
}: {
    locations: PaginatedResponse<any>;
    canCreateStores?: boolean;
    canManageStores?: boolean;
}) {
    const toggleStore = (location: any) => {
        if (location.is_active && !window.confirm(`Shut down ${location.name}? This store will be removed from active store workflows.`)) {
            return;
        }

        router.post(route('stores.toggle-active', location.id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stores" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Stores and Locations"
                    description="Dodoma warehouse and Tanzania region-based shop locations."
                    actionLabel={canCreateStores ? 'Add Store / Warehouse' : undefined}
                    actionHref={canCreateStores ? route('stores.create') : undefined}
                />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-4">Location</th>
                                        <th className="px-6 py-4">Type</th>
                                        <th className="px-6 py-4">Region</th>
                                        <th className="px-6 py-4">Status</th>
                                        <th className="px-6 py-4">Counts</th>
                                        {canManageStores ? <th className="px-6 py-4 text-right">Actions</th> : null}
                                    </tr>
                                </thead>
                                <tbody>
                                    {locations.data.map((location) => (
                                        <tr key={location.id} className="border-b border-border/60 last:border-b-0">
                                            <td className="px-6 py-4">
                                                <div className="font-medium">{location.name}</div>
                                                <div className="text-xs text-muted-foreground">{location.code}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <StatusBadge
                                                    status={location.type === 'warehouse' ? 'approved' : 'completed'}
                                                    label={location.type === 'warehouse' ? 'Warehouse' : 'Store'}
                                                    className={location.type === 'warehouse' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : ''}
                                                />
                                            </td>
                                            <td className="px-6 py-4">{location.region_name}</td>
                                            <td className="px-6 py-4">
                                                <StatusBadge status={location.is_active ? 'active' : 'inactive'} label={location.is_active ? 'Open' : 'Shut Down'} />
                                            </td>
                                            <td className="px-6 py-4 text-xs text-muted-foreground">
                                                {location.stocks_count} stocks / {location.orders_count} orders / {location.invoices_count} invoices
                                            </td>
                                            {canManageStores ? (
                                                <td className="px-6 py-4">
                                                    <div className="flex justify-end">
                                                        {location.type === 'shop' ? (
                                                            <Button
                                                                type="button"
                                                                variant={location.is_active ? 'ghost' : 'outline'}
                                                                size="sm"
                                                                className={location.is_active ? 'text-red-600 hover:text-red-700' : ''}
                                                                onClick={() => toggleStore(location)}
                                                            >
                                                                {location.is_active ? 'Shut Down' : 'Reopen'}
                                                            </Button>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground">Not available</span>
                                                        )}
                                                    </div>
                                                </td>
                                            ) : null}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <PaginationLinks links={locations.links} />
            </div>
        </AppLayout>
    );
}
