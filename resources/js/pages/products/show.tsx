import { PageHeader } from '@/components/shared/page-header';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

function formatMeters(value: number | string | null | undefined): string {
    const meters = Number(value ?? 0);

    return `${Number.isFinite(meters) ? meters.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '0'} m`;
}

export default function ProductShow({ product, canEdit }: { product: any; canEdit: boolean }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Products', href: '/products' },
        { title: product.brand_name, href: route('products.show', product.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={product.brand_name} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={product.brand_name} description={product.description ?? 'No product description provided.'} />

                <Card className="rounded-2xl shadow-sm">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Sub Products</CardTitle>
                        {canEdit ? (
                            <Button asChild variant="outline">
                                <Link href={route('products.edit', product.id)}>Edit Product</Link>
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {product.variants.map((variant: any) => {
                            const totalStock = variant.stocks.reduce((sum: number, stock: any) => sum + stock.quantity, 0);
                            const totalMeters = totalStock * Number(variant.meter_length ?? 0);

                            return (
                                <div key={variant.id} className="rounded-2xl border border-border/70 p-4">
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div className="text-lg font-semibold">{variant.color}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {variant.sku} / {Number(variant.meter_length).toLocaleString()} meters per roll
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <StatusBadge status={totalStock === 0 ? 'rejected' : totalStock <= variant.low_stock_threshold ? 'partially_received' : 'completed'} />
                                            <div className="text-sm font-semibold">{totalStock} rolls</div>
                                        </div>
                                    </div>

                                    <div className="mt-4 grid gap-4 md:grid-cols-3">
                                        <div className="rounded-xl bg-muted/40 p-3 text-sm">
                                            <div className="text-muted-foreground">Total Rolls</div>
                                            <div className="mt-1 font-semibold">{totalStock.toLocaleString()} rolls</div>
                                        </div>
                                        <div className="rounded-xl bg-muted/40 p-3 text-sm">
                                            <div className="text-muted-foreground">Total Meters</div>
                                            <div className="mt-1 font-semibold">{formatMeters(totalMeters)}</div>
                                        </div>
                                        <div className="rounded-xl bg-muted/40 p-3 text-sm">
                                            <div className="text-muted-foreground">Threshold</div>
                                            <div className="mt-1 font-semibold">{variant.low_stock_threshold} rolls</div>
                                        </div>
                                    </div>

                                    <div className="mt-4 overflow-x-auto">
                                        <table className="min-w-full text-sm">
                                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                                <tr>
                                                    <th className="rounded-l-xl px-4 py-3">Location</th>
                                                    <th className="px-4 py-3">Bucket</th>
                                                    <th className="rounded-r-xl px-4 py-3">Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {variant.stocks.map((stock: any) => (
                                                    <tr key={stock.id} className="border-b border-border/60 last:border-b-0">
                                                        <td className="px-4 py-3">{stock.location?.name}</td>
                                                        <td className="px-4 py-3">{stock.bucket}</td>
                                                        <td className="px-4 py-3">{stock.quantity}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
