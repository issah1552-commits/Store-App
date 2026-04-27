import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
];

interface ProductIndexProps {
    products: PaginatedResponse<any>;
    categories: { id: number; name: string }[];
    filters: {
        search?: string;
        category_id?: number | string;
        stock_status?: string;
    };
    canManageProducts: boolean;
}

export default function ProductIndex({ products, categories, filters, canManageProducts }: ProductIndexProps) {
    const { data, setData } = useForm({
        search: filters.search ?? '',
        category_id: filters.category_id ?? '',
        stock_status: filters.stock_status ?? 'all',
    });

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(route('products.index'), data, {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            });
        }, 250);

        return () => clearTimeout(timeout);
    }, [data]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Products" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Products"
                    description="Centralized product master data with stock visibility by location and bucket."
                    actionLabel={canManageProducts ? 'Add Product' : undefined}
                    actionHref={canManageProducts ? route('products.create') : undefined}
                />

                <Card className="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <Input value={data.search} onChange={(event) => setData('search', event.target.value)} placeholder="Search brand or variant..." />

                        <select
                            value={data.category_id}
                            onChange={(event) => setData('category_id', event.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>

                        <select
                            value={data.stock_status}
                            onChange={(event) => setData('stock_status', event.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="all">All stock states</option>
                            <option value="in_stock">In stock</option>
                            <option value="low_stock">Low stock</option>
                            <option value="out_of_stock">Out of stock</option>
                        </select>
                    </CardContent>
                </Card>

                {products.data.length ? (
                    <div className="grid gap-4">
                        {products.data.map((product) => (
                            <Card key={product.id} className="rounded-2xl shadow-sm">
                                <CardContent className="space-y-4 p-6">
                                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <Link href={route('products.show', product.id)} className="text-xl font-semibold tracking-tight hover:underline">
                                                {product.brand_name}
                                            </Link>
                                            <p className="mt-1 text-sm text-muted-foreground">{product.category}</p>
                                            <p className="mt-3 max-w-3xl text-sm text-muted-foreground">{product.description}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <StatusBadge status={product.is_out_of_stock ? 'rejected' : product.is_low_stock ? 'partially_received' : 'completed'} />
                                            <div className="text-right">
                                                <div className="text-sm text-muted-foreground">Total stock</div>
                                                <div className="text-lg font-semibold">{Number(product.total_stock).toLocaleString()} rolls</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="min-w-full text-sm">
                                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                                <tr>
                                                    <th className="rounded-l-xl px-4 py-3">Variant</th>
                                                    <th className="px-4 py-3">SKU</th>
                                                    <th className="px-4 py-3">Prices</th>
                                                    <th className="px-4 py-3">Stock by bucket</th>
                                                    <th className="rounded-r-xl px-4 py-3">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {product.variants.map((variant: any) => (
                                                    <tr key={variant.id} className="border-b border-border/60 last:border-b-0">
                                                        <td className="px-4 py-4">
                                                            <div className="font-medium">{variant.color}</div>
                                                            <div className="text-xs text-muted-foreground">{variant.meter_length} meters</div>
                                                        </td>
                                                        <td className="px-4 py-4">{variant.sku}</td>
                                                        <td className="px-4 py-4">
                                                            <div>{formatCurrencyTZS(variant.retail_price_tzs)} retail</div>
                                                            <div className="text-xs text-muted-foreground">{formatCurrencyTZS(variant.wholesale_price_tzs)} wholesale</div>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <div className="space-y-1">
                                                                {variant.stocks.length ? (
                                                                    variant.stocks.map((stock: any) => (
                                                                        <div key={stock.id} className="text-xs text-muted-foreground">
                                                                            {stock.location}: {stock.bucket} • {stock.quantity}
                                                                        </div>
                                                                    ))
                                                                ) : (
                                                                    <span className="text-xs text-muted-foreground">No stock snapshot</span>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            {variant.is_out_of_stock ? (
                                                                <StatusBadge status="rejected" className="bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200" />
                                                            ) : variant.is_low_stock ? (
                                                                <StatusBadge status="partially_received" className="bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200" />
                                                            ) : (
                                                                <StatusBadge status="completed" />
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {canManageProducts ? (
                                        <div className="flex justify-end">
                                            <Button asChild variant="outline">
                                                <Link href={route('products.edit', product.id)}>Edit Product</Link>
                                            </Button>
                                        </div>
                                    ) : null}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <EmptyState title="No products found" description="Adjust the filters or create the first centrally managed product." />
                )}

                <PaginationLinks links={products.links} />
            </div>
        </AppLayout>
    );
}
