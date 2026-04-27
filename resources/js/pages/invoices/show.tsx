import { PageHeader } from '@/components/shared/page-header';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

export default function InvoiceShow({ invoice, canMarkPaid, canVoid }: { invoice: any; canMarkPaid: boolean; canVoid: boolean }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Invoices', href: '/invoices' },
        { title: invoice.invoice_number, href: route('invoices.show', invoice.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={invoice.invoice_number} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={invoice.invoice_number} description={`${invoice.location?.name} • ${invoice.order?.order_number ?? 'Manual invoice'}`} />

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="rounded-2xl shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Invoice Items</CardTitle>
                            <div className="flex items-center gap-2">
                                <StatusBadge status={invoice.status} />
                                <StatusBadge status={invoice.payment_status} />
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {invoice.items.map((item: any) => (
                                <div key={item.id} className="rounded-2xl border border-border/70 p-4">
                                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div className="font-semibold">{item.description}</div>
                                            <div className="text-sm text-muted-foreground">Quantity {item.quantity}</div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                                            <div>
                                                <div className="text-muted-foreground">Unit Price</div>
                                                <div className="font-semibold">{formatCurrencyTZS(item.unit_price_tzs)}</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Line Total</div>
                                                <div className="font-semibold">{formatCurrencyTZS(item.line_total_tzs)}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl shadow-sm">
                        <CardHeader>
                            <CardTitle>Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div className="rounded-xl bg-muted/50 p-4">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatCurrencyTZS(invoice.subtotal_tzs)}</span>
                                </div>
                                <div className="mt-2 flex justify-between">
                                    <span className="text-muted-foreground">Tax</span>
                                    <span>{formatCurrencyTZS(invoice.tax_tzs)}</span>
                                </div>
                                <div className="mt-2 flex justify-between">
                                    <span className="text-muted-foreground">Discount</span>
                                    <span>{formatCurrencyTZS(invoice.discount_tzs)}</span>
                                </div>
                                <div className="mt-3 flex justify-between text-base font-semibold">
                                    <span>Total</span>
                                    <span>{formatCurrencyTZS(invoice.total_tzs)}</span>
                                </div>
                            </div>

                            <Button asChild className="w-full" variant="outline">
                                <Link href={route('invoices.print', invoice.id)}>Print View</Link>
                            </Button>

                            {canMarkPaid ? (
                                <Button className="w-full" onClick={() => router.post(route('invoices.mark-paid', invoice.id))}>
                                    Mark Paid
                                </Button>
                            ) : null}

                            {canVoid ? (
                                <Button variant="outline" className="w-full" onClick={() => router.post(route('invoices.void', invoice.id))}>
                                    Void Invoice
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
