import InputError from '@/components/input-error';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface ProductFormProps {
    mode: 'create' | 'edit';
    categories: { id: number; name: string }[];
    product?: any;
}

const baseBreadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
];

function formatMeters(value: number): string {
    return `${value.toLocaleString(undefined, { maximumFractionDigits: 2 })} m`;
}

export default function ProductForm({ mode, categories, product }: ProductFormProps) {
    const breadcrumbs = [
        ...baseBreadcrumbs,
        { title: mode === 'create' ? 'Add Product' : 'Edit Product', href: '#' },
    ];

    const initialVariants =
        product?.variants?.map((variant: any) => ({
            id: variant.id,
            sku: variant.sku,
            color: variant.color,
            meter_length: String(variant.meter_length),
            rolls: '',
            standard_cost_tzs: String(variant.standard_cost_tzs),
            wholesale_price_tzs: String(variant.wholesale_price_tzs),
            retail_price_tzs: String(variant.retail_price_tzs),
            low_stock_threshold: String(variant.low_stock_threshold),
        })) ?? [
            {
                color: '',
                meter_length: '',
                rolls: '1',
                standard_cost_tzs: '0',
                wholesale_price_tzs: '0',
                retail_price_tzs: '0',
                low_stock_threshold: '5',
            },
        ];

    const form = useForm({
        brand_name: product?.brand_name ?? '',
        color: product?.variants?.[0]?.color ?? '',
        category_id: String(product?.category_id ?? ''),
        description: product?.description ?? '',
        variants: initialVariants,
    });

    const totalRolls = form.data.variants.reduce((sum, item) => sum + Number(item.rolls || 0), 0);
    const totalMeters = form.data.variants.reduce((sum, item) => sum + Number(item.rolls || 0) * Number(item.meter_length || 0), 0);

    const updateVariant = (index: number, field: string, value: string) => {
        const nextVariants = [...form.data.variants];
        nextVariants[index] = { ...nextVariants[index], [field]: value };
        form.setData('variants', nextVariants);
    };

    const updateProductColor = (value: string) => {
        form.setData((data) => ({
            ...data,
            color: value,
            variants: data.variants.map((variant) => ({
                ...variant,
                color: value,
            })),
        }));
    };

    const addVariant = () => {
        form.setData('variants', [
            ...form.data.variants,
            {
                color: mode === 'create' ? form.data.color : '',
                meter_length: '',
                rolls: '1',
                standard_cost_tzs: '0',
                wholesale_price_tzs: '0',
                retail_price_tzs: '0',
                low_stock_threshold: '5',
            },
        ]);
    };

    const removeVariant = (index: number) => {
        form.setData(
            'variants',
            form.data.variants.filter((_, rowIndex) => rowIndex !== index),
        );
    };

    const submit = () => {
        if (mode === 'create') {
            form.post(route('products.store'));
            return;
        }

        form.put(route('products.update', product.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'Add Product' : 'Edit Product'} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title={mode === 'create' ? 'Add Product' : 'Edit Product'}
                    description="Brand is the parent product. Each variant row defines color, meter length, and opening rolls."
                />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="brand_name">Brand Name</Label>
                                <Input id="brand_name" value={form.data.brand_name} onChange={(event) => form.setData('brand_name', event.target.value)} />
                                <InputError message={form.errors.brand_name} />
                            </div>

                            {mode === 'create' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="color">Color</Label>
                                    <Input id="color" value={form.data.color} onChange={(event) => updateProductColor(event.target.value)} />
                                    <InputError message={form.errors.color} />
                                </div>
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="category_id">Category</Label>
                                    <select
                                        id="category_id"
                                        value={form.data.category_id}
                                        onChange={(event) => form.setData('category_id', event.target.value)}
                                        className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Select category</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.category_id} />
                                </div>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                className="min-h-28 rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <InputError message={form.errors.description} />
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">Sub Products / Variants</h2>
                                    <p className="text-sm text-muted-foreground">Opening rolls are posted as opening warehouse stock, not permanent product definition data.</p>
                                </div>
                                <Button type="button" variant="outline" onClick={addVariant}>
                                    Add Sub Product
                                </Button>
                            </div>

                            {form.data.variants.map((variant, index) => (
                                <div key={`${variant.id ?? 'new'}-${index}`} className="rounded-2xl border border-border/70 p-4">
                                    <div className={`grid gap-4 md:grid-cols-2 ${mode === 'create' ? 'xl:grid-cols-4' : 'xl:grid-cols-5'}`}>
                                        {mode === 'edit' ? (
                                            <div className="grid gap-2">
                                                <Label>Color</Label>
                                                <Input value={variant.color} onChange={(event) => updateVariant(index, 'color', event.target.value)} />
                                            </div>
                                        ) : null}
                                        <div className="grid gap-2">
                                            <Label>Meter Length</Label>
                                            <Input type="number" min="0" step="0.01" value={variant.meter_length} onChange={(event) => updateVariant(index, 'meter_length', event.target.value)} />
                                        </div>
                                        {mode === 'create' ? (
                                            <div className="grid gap-2">
                                                <Label>Opening Rolls</Label>
                                                <Input type="number" min="1" value={variant.rolls} onChange={(event) => updateVariant(index, 'rolls', event.target.value)} />
                                            </div>
                                        ) : (
                                            <div className="grid gap-2">
                                                <Label>SKU</Label>
                                                <Input value={variant.sku ?? 'Generated automatically'} readOnly />
                                            </div>
                                        )}
                                        <div className="grid gap-2">
                                            <Label>Low Stock Threshold</Label>
                                            <Input type="number" min="0" value={variant.low_stock_threshold} onChange={(event) => updateVariant(index, 'low_stock_threshold', event.target.value)} />
                                        </div>
                                        <div className="flex items-end">
                                            <Button type="button" variant="ghost" className="text-red-600 hover:text-red-700" onClick={() => removeVariant(index)} disabled={form.data.variants.length === 1}>
                                                Remove
                                            </Button>
                                        </div>

                                        {mode === 'edit' ? (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label>Standard Cost (TZS)</Label>
                                                    <Input type="number" min="0" value={variant.standard_cost_tzs} onChange={(event) => updateVariant(index, 'standard_cost_tzs', event.target.value)} />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Wholesale Price (TZS)</Label>
                                                    <Input type="number" min="0" value={variant.wholesale_price_tzs} onChange={(event) => updateVariant(index, 'wholesale_price_tzs', event.target.value)} />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Retail Price (TZS)</Label>
                                                    <Input type="number" min="0" value={variant.retail_price_tzs} onChange={(event) => updateVariant(index, 'retail_price_tzs', event.target.value)} />
                                                </div>
                                            </>
                                        ) : null}
                                    </div>
                                </div>
                            ))}

                            {mode === 'create' ? (
                                <div className="grid gap-2 rounded-2xl bg-muted/50 px-4 py-3 text-sm font-semibold">
                                    <div>Live Total Rolls: {totalRolls.toLocaleString()}</div>
                                    <div>Live Total Meters: {formatMeters(totalMeters)}</div>
                                </div>
                            ) : null}
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={submit} disabled={form.processing}>
                                {mode === 'create' ? 'Create Product' : 'Save Changes'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
