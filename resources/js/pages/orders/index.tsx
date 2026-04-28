import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { clickableLinkClassName } from '@/lib/link-styles';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Orders', href: '/orders' }];

export default function OrderIndex({ orders, canCreate }: { orders: PaginatedResponse<any>; canCreate: boolean }) {
    const { location_context } = usePage().props as any;
    const selectedLocation = location_context?.selected_location;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Orders" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Orders"
                    description={
                        selectedLocation
                            ? `Shop-facing orders scoped to ${selectedLocation.name}.`
                            : 'Shop-facing orders that consume retail stock only when completed.'
                    }
                    actionLabel={canCreate ? 'Create Order' : undefined}
                    actionHref={canCreate ? route('orders.create') : undefined}
                />

                {orders.data.length ? (
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-6 py-4">Order</th>
                                            <th className="px-6 py-4">Shop</th>
                                            <th className="px-6 py-4">Customer</th>
                                            <th className="px-6 py-4">Total</th>
                                            <th className="px-6 py-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {orders.data.map((order) => (
                                            <tr key={order.id} className="border-b border-border/60 last:border-b-0">
                                                <td className="px-6 py-4 font-medium">
                                                    <Link
                                                        href={route('orders.show', order.id)}
                                                        className={clickableLinkClassName}
                                                    >
                                                        {order.order_number}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">{order.location?.name}</td>
                                                <td className="px-6 py-4">{order.customer_name ?? 'Walk-in customer'}</td>
                                                <td className="px-6 py-4">{formatCurrencyTZS(order.total_tzs)}</td>
                                                <td className="px-6 py-4">
                                                    <StatusBadge status={order.status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No orders found" description="Create the first shop order to start tracking retail sales." />
                )}

                <PaginationLinks links={orders.links} />
            </div>
        </AppLayout>
    );
}
