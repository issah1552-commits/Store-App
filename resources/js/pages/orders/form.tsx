import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function OrderForm({ locations, variants }: { locations: any[]; variants: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Orders', href: '/orders' },
        { title: 'Create Order', href: '#' },
    ];

    const form = useForm({
        location_id: String(locations[0]?.id ?? ''),
        customer_name: '',
        customer_phone: '',
        discount_tzs: '0',
        notes: '',
        items: [{ product_variant_id: '', quantity: '1' }],
    });

    const scopedVariants = variants.filter((variant: any) => String(variant.location_id) === form.data.location_id);

    const updateItem = (index: number, field: string, value: string) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], [field]: value };
        form.setData('items', items);
    };

    const estimatedTotal = form.data.items.reduce((sum, item) => {
        const variant = scopedVariants.find((entry: any) => String(entry.product_variant_id) === String(item.product_variant_id));
        if (!variant) {
            return sum;
        }

        return sum + Number(item.quantity || 0) * Number(variant.price_tzs || 0);
    }, 0) - Number(form.data.discount_tzs || 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Order" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Create Order" description="Orders are shop-facing and will reduce retail stock only when completed." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="location_id">Shop</Label>
                                <select
                                    id="location_id"
                                    value={form.data.location_id}
                                    onChange={(event) => form.setData('location_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {locations.map((location) => (
                                        <option key={location.id} value={location.id}>
                                            {location.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="customer_name">Customer Name</Label>
                                <Input id="customer_name" value={form.data.customer_name} onChange={(event) => form.setData('customer_name', event.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="customer_phone">Customer Phone</Label>
                                <Input id="customer_phone" value={form.data.customer_phone} onChange={(event) => form.setData('customer_phone', event.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="discount_tzs">Discount (TZS)</Label>
                                <Input id="discount_tzs" type="number" min="0" value={form.data.discount_tzs} onChange={(event) => form.setData('discount_tzs', event.target.value)} />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">Order Items</h2>
                                <Button type="button" variant="outline" onClick={() => form.setData('items', [...form.data.items, { product_variant_id: '', quantity: '1' }])}>
                                    Add Item
                                </Button>
                            </div>

                            {form.data.items.map((item, index) => (
                                <div key={index} className="grid gap-4 rounded-2xl border border-border/70 p-4 md:grid-cols-[1fr_140px_auto]">
                                    <div className="grid gap-2">
                                        <Label>Retail Variant</Label>
                                        <select
                                            value={item.product_variant_id}
                                            onChange={(event) => updateItem(index, 'product_variant_id', event.target.value)}
                                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">Select retail variant</option>
                                            {scopedVariants.map((variant: any) => (
                                                <option key={variant.product_variant_id} value={variant.product_variant_id}>
                                                    {variant.brand_name} / {variant.color} / {variant.meter_length}m • {variant.available_quantity} available • {formatCurrencyTZS(variant.price_tzs)}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Quantity</Label>
                                        <Input type="number" min="1" value={item.quantity} onChange={(event) => updateItem(index, 'quantity', event.target.value)} />
                                    </div>
                                    <div className="flex items-end">
                                        <Button type="button" variant="ghost" className="text-red-600" disabled={form.data.items.length === 1} onClick={() => form.setData('items', form.data.items.filter((_, rowIndex) => rowIndex !== index))}>
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="rounded-2xl bg-muted/50 px-4 py-3 text-sm font-semibold">Estimated Total: {formatCurrencyTZS(estimatedTotal)}</div>

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={() => form.post(route('orders.store'))} disabled={form.processing}>
                                Create Order
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
