import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function InternalMovementForm({ locations, variants }: { locations: any[]; variants: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Internal Movements', href: '/internal-movements' },
        { title: 'Create Movement', href: '#' },
    ];

    const form = useForm({
        location_id: String(locations[0]?.id ?? ''),
        notes: '',
        items: [{ product_variant_id: '', quantity: '1', notes: '' }],
    });

    const scopedVariants = variants.filter((variant: any) => String(variant.location_id) === form.data.location_id);

    const updateItem = (index: number, field: string, value: string) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], [field]: value };
        form.setData('items', items);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Internal Movement" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Create Internal Movement" description="Moves stock from wholesale to retail. Over-threshold requests will escalate for approval." />

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
                                <Label htmlFor="notes">Reason</Label>
                                <Input id="notes" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} placeholder="Why is this movement needed?" />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">Movement Items</h2>
                                <Button type="button" variant="outline" onClick={() => form.setData('items', [...form.data.items, { product_variant_id: '', quantity: '1', notes: '' }])}>
                                    Add Item
                                </Button>
                            </div>

                            {form.data.items.map((item, index) => (
                                <div key={index} className="grid gap-4 rounded-2xl border border-border/70 p-4 md:grid-cols-[1fr_150px_auto]">
                                    <div className="grid gap-2">
                                        <Label>Variant</Label>
                                        <select
                                            value={item.product_variant_id}
                                            onChange={(event) => updateItem(index, 'product_variant_id', event.target.value)}
                                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">Select variant</option>
                                            {scopedVariants.map((variant: any) => (
                                                <option key={variant.product_variant_id} value={variant.product_variant_id}>
                                                    {variant.brand_name} / {variant.color} / {variant.meter_length}m • Available {variant.available_quantity}
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

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={() => form.post(route('internal-movements.store'))} disabled={form.processing}>
                                Submit Movement
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
