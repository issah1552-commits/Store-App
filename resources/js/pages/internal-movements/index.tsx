import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { clickableLinkClassName } from '@/lib/link-styles';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Internal Movements', href: '/internal-movements' },
];

export default function InternalMovementIndex({ movements, canCreate }: { movements: PaginatedResponse<any>; canCreate: boolean }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Internal Movements" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Internal Wholesale to Retail Movements"
                    description="Threshold-aware internal shop movements with escalation, approval, and reversal controls."
                    actionLabel={canCreate ? 'Create Movement' : undefined}
                    actionHref={canCreate ? route('internal-movements.create') : undefined}
                />

                {movements.data.length ? (
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-6 py-4">Code</th>
                                            <th className="px-6 py-4">Shop</th>
                                            <th className="px-6 py-4">Requested By</th>
                                            <th className="px-6 py-4">Quantity</th>
                                            <th className="px-6 py-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {movements.data.map((movement) => (
                                            <tr key={movement.id} className="border-b border-border/60 last:border-b-0">
                                                <td className="px-6 py-4 font-medium">
                                                    <Link href={route('internal-movements.show', movement.id)} className={clickableLinkClassName}>
                                                        {movement.code}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">{movement.location?.name}</td>
                                                <td className="px-6 py-4">{movement.requester?.name}</td>
                                                <td className="px-6 py-4">{movement.total_quantity}</td>
                                                <td className="px-6 py-4">
                                                    <StatusBadge status={movement.status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No internal movements found" description="Create a wholesale to retail movement for one of your shops." />
                )}

                <PaginationLinks links={movements.links} />
            </div>
        </AppLayout>
    );
}
