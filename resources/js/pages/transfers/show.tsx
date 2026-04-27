import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

export default function TransferShow({ transfer, canApprove, canDispatch, canReceive, canCloseVariance }: { transfer: any; canApprove: boolean; canDispatch: boolean; canReceive: boolean; canCloseVariance: boolean }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Transfers', href: '/transfers' },
        { title: transfer.code, href: route('transfers.show', transfer.id) },
    ];

    const dispatchForm = useForm({
        notes: '',
        items: transfer.items.map((item: any) => ({
            product_variant_id: item.product_variant_id,
            quantity: String(Math.max((item.approved_quantity ?? item.requested_quantity) - item.dispatched_quantity, 0)),
        })),
    });

    const receiptForm = useForm({
        notes: '',
        items: transfer.items.map((item: any) => ({
            product_variant_id: item.product_variant_id,
            quantity: String(Math.max(item.dispatched_quantity - item.received_quantity, 0)),
        })),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={transfer.code} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={transfer.code} description={`${transfer.source_location?.name ?? transfer.sourceLocation?.name} to ${transfer.destination_location?.name ?? transfer.destinationLocation?.name}`} />

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="rounded-2xl shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Transfer Items</CardTitle>
                            <StatusBadge status={transfer.status} />
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {transfer.items.length ? (
                                transfer.items.map((item: any) => (
                                    <div key={item.id} className="rounded-2xl border border-border/70 p-4">
                                        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <div className="font-semibold">{item.product_variant.product.brand_name}</div>
                                                <div className="text-sm text-muted-foreground">
                                                    {item.product_variant.color} / {item.product_variant.meter_length}m / {item.product_variant.sku}
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                                <div>
                                                    <div className="text-muted-foreground">Requested</div>
                                                    <div className="font-semibold">{item.requested_quantity}</div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">Approved</div>
                                                    <div className="font-semibold">{item.approved_quantity ?? '-'}</div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">Dispatched</div>
                                                    <div className="font-semibold">{item.dispatched_quantity}</div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">Received</div>
                                                    <div className="font-semibold">{item.received_quantity}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <EmptyState title="No transfer items" description="This transfer does not yet contain items." />
                            )}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl shadow-sm">
                        <CardHeader>
                            <CardTitle>Workflow Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {canApprove ? (
                                <Button className="w-full" onClick={() => router.post(route('transfers.approve', transfer.id))}>
                                    Approve Transfer
                                </Button>
                            ) : null}

                            {canDispatch ? (
                                <div className="space-y-3 rounded-2xl border border-border/70 p-4">
                                    <div className="font-medium">Dispatch</div>
                                    {dispatchForm.data.items.map((item, index) => (
                                        <Input
                                            key={item.product_variant_id}
                                            type="number"
                                            min="0"
                                            value={item.quantity}
                                            onChange={(event) => {
                                                const items = [...dispatchForm.data.items];
                                                items[index] = { ...items[index], quantity: event.target.value };
                                                dispatchForm.setData('items', items);
                                            }}
                                        />
                                    ))}
                                    <Button className="w-full" onClick={() => dispatchForm.post(route('transfers.dispatch', transfer.id))}>
                                        Dispatch Selected Quantities
                                    </Button>
                                </div>
                            ) : null}

                            {canReceive ? (
                                <div className="space-y-3 rounded-2xl border border-border/70 p-4">
                                    <div className="font-medium">Confirm Receipt</div>
                                    {receiptForm.data.items.map((item, index) => (
                                        <Input
                                            key={item.product_variant_id}
                                            type="number"
                                            min="0"
                                            value={item.quantity}
                                            onChange={(event) => {
                                                const items = [...receiptForm.data.items];
                                                items[index] = { ...items[index], quantity: event.target.value };
                                                receiptForm.setData('items', items);
                                            }}
                                        />
                                    ))}
                                    <Button className="w-full" onClick={() => receiptForm.post(route('transfers.receive', transfer.id))}>
                                        Confirm Receipt
                                    </Button>
                                </div>
                            ) : null}

                            {canCloseVariance ? (
                                <Button variant="outline" className="w-full" onClick={() => router.post(route('transfers.close', transfer.id))}>
                                    Close Transfer
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
                        {transfer.histories?.length ? (
                            transfer.histories.map((history: any) => (
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
                            ))
                        ) : (
                            <EmptyState title="No timeline entries" description="Status transitions will appear here once activity begins." />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
