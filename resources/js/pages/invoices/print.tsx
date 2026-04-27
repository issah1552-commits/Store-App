import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrencyTZS } from '@/lib/format';
import { Head } from '@inertiajs/react';

export default function InvoicePrint({ invoice }: { invoice: any }) {
    return (
        <>
            <Head title={`Print ${invoice.invoice_number}`} />
            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <Card className="rounded-2xl shadow-sm">
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div>
                            <CardTitle className="text-3xl">{invoice.invoice_number}</CardTitle>
                            <p className="mt-2 text-sm text-muted-foreground">{invoice.location?.name} • Tanzania • Currency TZS</p>
                        </div>
                        <div className="flex gap-2">
                            <StatusBadge status={invoice.status} />
                            <StatusBadge status={invoice.payment_status} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl bg-muted/40 p-4">
                                <div className="text-sm text-muted-foreground">Order Reference</div>
                                <div className="mt-1 font-semibold">{invoice.order?.order_number ?? 'N/A'}</div>
                            </div>
                            <div className="rounded-xl bg-muted/40 p-4">
                                <div className="text-sm text-muted-foreground">Issued By</div>
                                <div className="mt-1 font-semibold">{invoice.issuer?.name}</div>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3">Description</th>
                                        <th className="px-4 py-3">Quantity</th>
                                        <th className="px-4 py-3">Unit Price</th>
                                        <th className="px-4 py-3">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoice.items.map((item: any) => (
                                        <tr key={item.id} className="border-b border-border/60 last:border-b-0">
                                            <td className="px-4 py-3">{item.description}</td>
                                            <td className="px-4 py-3">{item.quantity}</td>
                                            <td className="px-4 py-3">{formatCurrencyTZS(item.unit_price_tzs)}</td>
                                            <td className="px-4 py-3">{formatCurrencyTZS(item.line_total_tzs)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="ml-auto max-w-sm space-y-2 rounded-xl bg-muted/40 p-4 text-sm">
                            <div className="flex justify-between"><span>Subtotal</span><span>{formatCurrencyTZS(invoice.subtotal_tzs)}</span></div>
                            <div className="flex justify-between"><span>Tax</span><span>{formatCurrencyTZS(invoice.tax_tzs)}</span></div>
                            <div className="flex justify-between"><span>Discount</span><span>{formatCurrencyTZS(invoice.discount_tzs)}</span></div>
                            <div className="flex justify-between text-base font-semibold"><span>Total</span><span>{formatCurrencyTZS(invoice.total_tzs)}</span></div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
