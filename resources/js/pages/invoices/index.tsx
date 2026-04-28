import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { clickableLinkClassName } from '@/lib/link-styles';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Invoices', href: '/invoices' }];

export default function InvoiceIndex({ invoices, canCreate }: { invoices: PaginatedResponse<any>; canCreate: boolean }) {
    const { location_context } = usePage().props as any;
    const selectedLocation = location_context?.selected_location;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Invoices" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Invoices"
                    description={
                        selectedLocation
                            ? `Print-friendly TZS invoices scoped to ${selectedLocation.name}.`
                            : 'Print-friendly TZS invoices for shop operations only.'
                    }
                    actionLabel={canCreate ? 'Create Invoice' : undefined}
                    actionHref={canCreate ? route('invoices.create') : undefined}
                />

                {invoices.data.length ? (
                    <Card className="rounded-2xl shadow-sm">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-6 py-4">Invoice</th>
                                            <th className="px-6 py-4">Shop</th>
                                            <th className="px-6 py-4">Payment</th>
                                            <th className="px-6 py-4">Total</th>
                                            <th className="px-6 py-4">Issued</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {invoices.data.map((invoice) => (
                                            <tr key={invoice.id} className="border-b border-border/60 last:border-b-0">
                                                <td className="px-6 py-4 font-medium">
                                                    <Link href={route('invoices.show', invoice.id)} className={clickableLinkClassName}>
                                                        {invoice.invoice_number}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4">{invoice.location?.name}</td>
                                                <td className="px-6 py-4">
                                                    <StatusBadge status={invoice.payment_status} />
                                                </td>
                                                <td className="px-6 py-4">{formatCurrencyTZS(invoice.total_tzs)}</td>
                                                <td className="px-6 py-4">{invoice.issued_at ? new Date(invoice.issued_at).toLocaleDateString() : '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <EmptyState title="No invoices found" description="Invoices created from completed shop sales will appear here." />
                )}

                <PaginationLinks links={invoices.links} />
            </div>
        </AppLayout>
    );
}
