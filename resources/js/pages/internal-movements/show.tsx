import { PageHeader } from '@/components/shared/page-header';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

export default function InternalMovementShow({ movement, canApprove, canReverse }: { movement: any; canApprove: boolean; canReverse: boolean }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Internal Movements', href: '/internal-movements' },
        { title: movement.code, href: route('internal-movements.show', movement.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={movement.code} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={movement.code} description={`${movement.location?.name} wholesale to retail movement`} />

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="rounded-2xl shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Movement Items</CardTitle>
                            <StatusBadge status={movement.status} />
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {movement.items.map((item: any) => (
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
                                                <div className="text-muted-foreground">Value</div>
                                                <div className="font-semibold">{formatCurrencyTZS(item.unit_value_tzs)}</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Route</div>
                                                <div className="font-semibold">{item.source_bucket} → {item.destination_bucket}</div>
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
                            {canApprove ? (
                                <>
                                    <Button className="w-full" onClick={() => router.post(route('internal-movements.approve', movement.id))}>
                                        Approve and Post
                                    </Button>
                                    <Button variant="outline" className="w-full" onClick={() => router.post(route('internal-movements.reject', movement.id))}>
                                        Reject
                                    </Button>
                                </>
                            ) : null}

                            {canReverse ? (
                                <Button variant="outline" className="w-full" onClick={() => router.post(route('internal-movements.reverse', movement.id))}>
                                    Reverse Movement
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>

                <Card className="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Timeline</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {movement.histories.map((history: any) => (
                            <div key={history.id} className="rounded-xl border border-border/70 p-4">
                                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div className="font-medium">{history.actor?.name}</div>
                                        <div className="text-sm text-muted-foreground">{history.reason ?? 'Workflow update'}</div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {history.from_status ? <StatusBadge status={history.from_status} /> : null}
                                        <span className="text-muted-foreground">→</span>
                                        <StatusBadge status={history.to_status} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
