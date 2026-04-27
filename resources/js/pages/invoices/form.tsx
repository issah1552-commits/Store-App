import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function InvoiceForm({ orders }: { orders: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Invoices', href: '/invoices' },
        { title: 'Create Invoice', href: '#' },
    ];

    const form = useForm({
        order_id: '',
        tax_tzs: '0',
        discount_tzs: '0',
        notes: '',
    });

    const selectedOrder = orders.find((order) => String(order.id) === form.data.order_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Invoice" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Create Invoice" description="Invoices are shop-facing, region-scoped, and displayed only in TZS." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="order_id">Completed Order</Label>
                                <select
                                    id="order_id"
                                    value={form.data.order_id}
                                    onChange={(event) => form.setData('order_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select order</option>
                                    {orders.map((order) => (
                                        <option key={order.id} value={order.id}>
                                            {order.order_number} • {order.location?.name} • {formatCurrencyTZS(order.total_tzs)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tax_tzs">Tax (TZS)</Label>
                                <Input id="tax_tzs" type="number" min="0" value={form.data.tax_tzs} onChange={(event) => form.setData('tax_tzs', event.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="discount_tzs">Discount (TZS)</Label>
                                <Input id="discount_tzs" type="number" min="0" value={form.data.discount_tzs} onChange={(event) => form.setData('discount_tzs', event.target.value)} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Input id="notes" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                            </div>
                        </div>

                        {selectedOrder ? (
                            <div className="rounded-2xl border border-border/70 p-4 text-sm">
                                <div className="font-semibold">{selectedOrder.order_number}</div>
                                <div className="mt-1 text-muted-foreground">{selectedOrder.location?.name}</div>
                                <div className="mt-3 font-medium">Projected Total: {formatCurrencyTZS(Number(selectedOrder.total_tzs) + Number(form.data.tax_tzs || 0) - Number(form.data.discount_tzs || 0))}</div>
                            </div>
                        ) : null}

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={() => form.post(route('invoices.store'))} disabled={form.processing || !form.data.order_id}>
                                Create Invoice
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
