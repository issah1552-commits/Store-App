import InputError from '@/components/input-error';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function TransferForm({ sourceLocations, destinationLocations, variants }: { sourceLocations: any[]; destinationLocations: any[]; variants: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Transfers', href: '/transfers' },
        { title: 'Create Transfer', href: '#' },
    ];

    const form = useForm({
        source_location_id: String(sourceLocations[0]?.id ?? ''),
        destination_location_id: '',
        notes: '',
        items: [{ product_variant_id: '', requested_quantity: '1' }],
    });

    const updateItem = (index: number, field: string, value: string) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], [field]: value };
        form.setData('items', items);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Transfer" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Create Transfer" description="Requests do not change stock until the transfer is approved and dispatched." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="source_location_id">Source Warehouse</Label>
                                <select
                                    id="source_location_id"
                                    value={form.data.source_location_id}
                                    onChange={(event) => form.setData('source_location_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {sourceLocations.map((location) => (
                                        <option key={location.id} value={location.id}>
                                            {location.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="destination_location_id">Destination Shop</Label>
                                <select
                                    id="destination_location_id"
                                    value={form.data.destination_location_id}
                                    onChange={(event) => form.setData('destination_location_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select shop</option>
                                    {destinationLocations.map((location) => (
                                        <option key={location.id} value={location.id}>
                                            {location.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.destination_location_id} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <textarea
                                id="notes"
                                className="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.data.notes}
                                onChange={(event) => form.setData('notes', event.target.value)}
                            />
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">Transfer Items</h2>
                                <Button type="button" variant="outline" onClick={() => form.setData('items', [...form.data.items, { product_variant_id: '', requested_quantity: '1' }])}>
                                    Add Item
                                </Button>
                            </div>

                            {form.data.items.map((item, index) => (
                                <div key={index} className="grid gap-4 rounded-2xl border border-border/70 p-4 md:grid-cols-[1fr_180px_auto]">
                                    <div className="grid gap-2">
                                        <Label>Variant</Label>
                                        <select
                                            value={item.product_variant_id}
                                            onChange={(event) => updateItem(index, 'product_variant_id', event.target.value)}
                                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">Select variant</option>
                                            {variants.map((variant) => (
                                                <option key={variant.id} value={variant.id}>
                                                    {variant.product?.brand_name} / {variant.color} / {variant.meter_length}m / {variant.sku}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Requested Quantity</Label>
                                        <Input type="number" min="1" value={item.requested_quantity} onChange={(event) => updateItem(index, 'requested_quantity', event.target.value)} />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-red-600"
                                            disabled={form.data.items.length === 1}
                                            onClick={() => form.setData('items', form.data.items.filter((_, rowIndex) => rowIndex !== index))}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            <InputError message={form.errors.items} />
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={() => form.post(route('transfers.store'))} disabled={form.processing}>
                                Submit for Approval
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
