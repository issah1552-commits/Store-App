import { PageHeader } from '@/components/shared/page-header';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

export default function OrderShow({ order, canComplete, canCancel }: { order: any; canComplete: boolean; canCancel: boolean }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Orders', href: '/orders' },
        { title: order.order_number, href: route('orders.show', order.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={order.order_number} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={order.order_number} description={`${order.location?.name} • ${order.customer_name ?? 'Walk-in customer'}`} />

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="rounded-2xl shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Order Items</CardTitle>
                            <StatusBadge status={order.status} />
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {order.items.map((item: any) => (
                                <div key={item.id} className="rounded-2xl border border-border/70 p-4">
                                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div className="font-semibold">{item.product_variant.product.brand_name}</div>
                                            <div className="text-sm text-muted-foreground">{item.product_variant.color} / {item.product_variant.meter_length}m</div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                                            <div>
                                                <div className="text-muted-foreground">Quantity</div>
                                                <div className="font-semibold">{item.quantity}</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Unit Price</div>
                                                <div className="font-semibold">{formatCurrencyTZS(item.unit_price_tzs)}</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Line Total</div>
                                                <div className="font-semibold">{formatCurrencyTZS(item.line_total_tzs)}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl shadow-sm">
                        <CardHeader>
                            <CardTitle>Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="rounded-xl bg-muted/50 p-4 text-sm">
                                <div className="text-muted-foreground">Subtotal</div>
                                <div className="mt-1 font-semibold">{formatCurrencyTZS(order.subtotal_tzs)}</div>
                                <div className="mt-3 text-muted-foreground">Discount</div>
                                <div className="mt-1 font-semibold">{formatCurrencyTZS(order.discount_tzs)}</div>
                                <div className="mt-3 text-muted-foreground">Total</div>
                                <div className="mt-1 text-lg font-semibold">{formatCurrencyTZS(order.total_tzs)}</div>
                            </div>

                            {canComplete ? (
                                <Button className="w-full" onClick={() => router.post(route('orders.complete', order.id))}>
                                    Complete Order
                                </Button>
                            ) : null}

                            {canCancel ? (
                                <Button variant="outline" className="w-full" onClick={() => router.post(route('orders.cancel', order.id))}>
                                    Cancel Order
                                </Button>
                            ) : null}

                            {order.status === 'completed' ? (
                                <Button variant="secondary" className="w-full" asChild>
                                    <Link href={route('invoices.create')}>Create Invoice</Link>
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
