import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/reports' }];

export default function ReportIndex({ reportType, reportTypes, dataset, filters, buckets }: { reportType: string; reportTypes: any[]; dataset: any; filters: any; buckets: any[] }) {
    const { location_context } = usePage().props as any;
    const selectedLocation = location_context?.selected_location;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Reports"
                    description={
                        selectedLocation
                            ? `Filterable operational reporting scoped to ${selectedLocation.name}.`
                            : 'Filterable operational reporting across stock, transfers, movements, sales, invoices, and audit events.'
                    }
                />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="flex flex-col gap-4 p-6 md:flex-row">
                        <select
                            value={reportType}
                            onChange={(event) => router.get(route('reports.index'), { ...filters, type: event.target.value }, { preserveState: true, preserveScroll: true, replace: true })}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            {reportTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>

                        {reportType === 'stock' ? (
                            <select
                                value={filters.bucket ?? ''}
                                onChange={(event) => router.get(route('reports.index'), { ...filters, bucket: event.target.value }, { preserveState: true, preserveScroll: true, replace: true })}
                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All buckets</option>
                                {buckets.map((bucket) => (
                                    <option key={bucket.value} value={bucket.value}>
                                        {bucket.label}
                                    </option>
                                ))}
                            </select>
                        ) : null}
                    </CardContent>
                </Card>

                {dataset.data?.length ? (
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                {reportType === 'stock' ? (
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-muted/50 text-left text-muted-foreground">
                                            <tr>
                                                <th className="px-6 py-4">Location</th>
                                                <th className="px-6 py-4">Variant</th>
                                                <th className="px-6 py-4">Bucket</th>
                                                <th className="px-6 py-4">Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {dataset.data.map((row: any) => (
                                                <tr key={row.id} className="border-b border-border/60 last:border-b-0">
                                                    <td className="px-6 py-4">{row.location?.name}</td>
                                                    <td className="px-6 py-4">{row.product_variant?.product?.brand_name} / {row.product_variant?.color}</td>
                                                    <td className="px-6 py-4">{row.bucket}</td>
                                                    <td className="px-6 py-4">{row.quantity}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : (
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-muted/50 text-left text-muted-foreground">
                                            <tr>
                                                <th className="px-6 py-4">Reference</th>
                                                <th className="px-6 py-4">Location / Route</th>
                                                <th className="px-6 py-4">Owner</th>
                                                <th className="px-6 py-4">Status / Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {dataset.data.map((row: any) => (
                                                <tr key={row.id} className="border-b border-border/60 last:border-b-0">
                                                    <td className="px-6 py-4 font-medium">{row.code ?? row.order_number ?? row.invoice_number ?? row.action}</td>
                                                    <td className="px-6 py-4">
                                                        {row.location?.name ?? row.source_location?.name ?? row.sourceLocation?.name ?? row.location_name}
                                                        {(row.destination_location?.name ?? row.destinationLocation?.name) ? (
                                                            <div className="text-xs text-muted-foreground">to {row.destination_location?.name ?? row.destinationLocation?.name}</div>
                                                        ) : null}
                                                    </td>
                                                    <td className="px-6 py-4">{row.requester?.name ?? row.ordered_by?.name ?? row.orderedBy?.name ?? row.issuer?.name ?? row.user?.name ?? '-'}</td>
                                                    <td className="px-6 py-4">
                                                        {row.status || row.payment_status ? <StatusBadge status={row.payment_status ?? row.status} /> : null}
                                                        {row.total_tzs ? <div className="mt-1 text-xs text-muted-foreground">{formatCurrencyTZS(row.total_tzs)}</div> : null}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No report rows" description="No records matched the current report filters." />
                )}

                <PaginationLinks links={dataset.links} />
            </div>
        </AppLayout>
    );
}
