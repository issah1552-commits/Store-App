import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stores', href: '/stores' }];

export default function StoresIndex({ locations }: { locations: PaginatedResponse<any> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stores" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Stores and Locations" description="Dodoma warehouse and Tanzania region-based shop locations." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-4">Location</th>
                                        <th className="px-6 py-4">Type</th>
                                        <th className="px-6 py-4">Region</th>
                                        <th className="px-6 py-4">Counts</th>
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
                                                <StatusBadge status={location.type === 'warehouse' ? 'approved' : 'completed'} className={location.type === 'warehouse' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : ''} />
                                            </td>
                                            <td className="px-6 py-4">{location.region_name}</td>
                                            <td className="px-6 py-4 text-xs text-muted-foreground">
                                                {location.stocks_count} stocks • {location.orders_count} orders • {location.invoices_count} invoices
                                            </td>
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
