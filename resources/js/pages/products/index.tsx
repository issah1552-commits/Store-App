import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { clickableLinkClassName } from '@/lib/link-styles';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Products', href: '/products' }];

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

function formatMeters(value: number | string | null | undefined): string {
    const meters = Number(value ?? 0);

    return `${Number.isFinite(meters) ? meters.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '0'} m`;
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
                    description="Open a product to view its subproducts, stock buckets, and product details."
                    actionLabel={canManageProducts ? 'Add Product' : undefined}
                    actionHref={canManageProducts ? route('products.create') : undefined}
                />

                <Card className="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <Input value={data.search} onChange={(event) => setData('search', event.target.value)} placeholder="Search product name..." />

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
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-6 py-4">Name</th>
                                            <th className="px-6 py-4">Total Meters</th>
                                            <th className="px-6 py-4">Total Rolls</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {products.data.map((product) => (
                                            <tr key={product.id} className="border-b border-border/60 last:border-b-0">
                                                <td className="px-6 py-4">
                                                    <Link href={route('products.show', product.id)} className={clickableLinkClassName}>
                                                        {product.brand_name}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4 font-medium">{formatMeters(product.total_meters)}</td>
                                                <td className="px-6 py-4 font-medium">{Number(product.total_stock ?? 0).toLocaleString()} rolls</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No products found" description="Adjust the filters or create the first centrally managed product." />
                )}

                <PaginationLinks links={products.links} />
            </div>
        </AppLayout>
    );
}
