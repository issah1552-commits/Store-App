import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfers', href: '/transfers' },
];

export default function TransferIndex({ transfers, statuses, filters, canCreate }: { transfers: PaginatedResponse<any>; statuses: any[]; filters: any; canCreate: boolean }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transfers" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Warehouse Transfers"
                    description="Warehouse to shop transfers with approval, dispatch, receipt confirmation, and variance closeout."
                    actionLabel={canCreate ? 'Create Transfer' : undefined}
                    actionHref={canCreate ? route('transfers.create') : undefined}
                />

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        className={`rounded-full px-3 py-2 text-sm ${!filters.status ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}
                        onClick={() => router.get(route('transfers.index'), {}, { preserveState: true, preserveScroll: true, replace: true })}
                    >
                        All
                    </button>
                    {statuses.map((status) => (
                        <button
                            key={status.value}
                            type="button"
                            className={`rounded-full px-3 py-2 text-sm ${filters.status === status.value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}
                            onClick={() => router.get(route('transfers.index'), { status: status.value }, { preserveState: true, preserveScroll: true, replace: true })}
                        >
                            {status.label}
                        </button>
                    ))}
                </div>

                {transfers.data.length ? (
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-6 py-4">Code</th>
                                            <th className="px-6 py-4">Route</th>
                                            <th className="px-6 py-4">Requested By</th>
                                            <th className="px-6 py-4">Items</th>
                                            <th className="px-6 py-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transfers.data.map((transfer) => (
                                            <tr key={transfer.id} className="border-b border-border/60 last:border-b-0">
                                                <td className="px-6 py-4 font-medium">
                                                    <Link href={route('transfers.show', transfer.id)} className="hover:underline">
                                                        {transfer.code}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div>{transfer.source_location?.name ?? transfer.sourceLocation?.name}</div>
                                                    <div className="text-xs text-muted-foreground">to {transfer.destination_location?.name ?? transfer.destinationLocation?.name}</div>
                                                </td>
                                                <td className="px-6 py-4">{transfer.requester?.name}</td>
                                                <td className="px-6 py-4">{transfer.items_count}</td>
                                                <td className="px-6 py-4">
                                                    <StatusBadge status={transfer.status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No transfers found" description="Create the first warehouse-to-shop transfer request." />
                )}

                <PaginationLinks links={transfers.links} />
            </div>
        </AppLayout>
    );
}
