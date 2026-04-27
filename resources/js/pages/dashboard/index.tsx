import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { StatCard, type StatCardTone, type StatCardTrendDirection } from '@/components/shared/stat-card';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectSeparator, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { type BreadcrumbItem, type LocationSummary } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeftRight, Box, Building2, DollarSign, TriangleAlert, type LucideIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

type DashboardCardVisual = {
    icon: LucideIcon;
    tone: StatCardTone;
    trend: {
        value: string;
        direction: StatCardTrendDirection;
    };
};

function resolveCardVisual(title: string): DashboardCardVisual {
    const normalized = title.toLowerCase();

    if (normalized.includes('low stock') || normalized.includes('pending')) {
        return {
            icon: TriangleAlert,
            tone: 'orange',
            trend: {
                value: '-3',
                direction: 'negative',
            },
        };
    }

    if (normalized.includes('sales') || normalized.includes('invoice') || normalized.includes('order')) {
        return {
            icon: DollarSign,
            tone: 'green',
            trend: {
                value: '+8.2%',
                direction: 'positive',
            },
        };
    }

    if (normalized.includes('transfer') || normalized.includes('dispatch') || normalized.includes('receipt')) {
        return {
            icon: ArrowLeftRight,
            tone: 'purple',
            trend: {
                value: '+2',
                direction: 'positive',
            },
        };
    }

    return {
        icon: Box,
        tone: 'blue',
        trend: {
            value: '+12.5%',
            direction: 'positive',
        },
    };
}

export default function DashboardIndex({
    metrics,
    locations,
    filters,
}: {
    metrics: any;
    locations: LocationSummary[];
    filters: { location_id?: number | null };
}) {
    const { auth } = usePage().props as any;
    const selectedLocation = filters.location_id ? String(filters.location_id) : 'all';
    const activeLocation = locations.find((location) => String(location.id) === selectedLocation) ?? null;

    const warehouseLocations = locations.filter((location) => location.type === 'warehouse');
    const shopLocations = locations.filter((location) => location.type === 'shop');

    const handleLocationChange = (value: string) => {
        router.get(
            route('dashboard'),
            { location_id: value },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Enterprise Inventory Dashboard"
                    description={`Signed in as ${auth.user?.role?.display_name ?? 'User'} - Viewing ${activeLocation?.name ?? 'all accessible locations'}`}
                    actions={
                        <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                            <div className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-blue-50 px-4 text-sm font-semibold uppercase tracking-[0.18em] text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                                <Building2 className="h-4 w-4" />
                                Viewing
                            </div>

                            <Select value={selectedLocation} onValueChange={handleLocationChange}>
                                <SelectTrigger className="h-11 min-w-full rounded-2xl border-2 border-blue-500 bg-white px-5 text-left text-base font-medium text-slate-900 shadow-[0_8px_24px_rgba(37,99,235,0.08)] focus:ring-4 focus:ring-blue-100 sm:min-w-[320px] dark:border-blue-500 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-blue-500/20">
                                    <SelectValue placeholder="All Locations - Overview" />
                                </SelectTrigger>
                                <SelectContent className="rounded-2xl border-blue-100 shadow-[0_16px_40px_rgba(15,23,42,0.12)] dark:border-slate-800">
                                    <SelectGroup>
                                        <SelectLabel>Overview</SelectLabel>
                                        <SelectItem value="all">All Locations - Overview</SelectItem>
                                    </SelectGroup>

                                    {warehouseLocations.length ? (
                                        <>
                                            <SelectSeparator />
                                            <SelectGroup>
                                                <SelectLabel>Warehouses</SelectLabel>
                                                {warehouseLocations.map((location) => (
                                                    <SelectItem key={location.id} value={String(location.id)}>
                                                        {location.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </>
                                    ) : null}

                                    {shopLocations.length ? (
                                        <>
                                            <SelectSeparator />
                                            <SelectGroup>
                                                <SelectLabel>Stores</SelectLabel>
                                                {shopLocations.map((location) => (
                                                    <SelectItem key={location.id} value={String(location.id)}>
                                                        {location.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </>
                                    ) : null}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />

                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    {(metrics.cards ?? []).map((card: any) => {
                        const visual = resolveCardVisual(String(card.label));

                        return (
                            <StatCard
                                key={card.label}
                                title={card.label}
                                value={typeof card.value === 'number' ? card.value.toLocaleString() : card.value}
                                icon={visual.icon}
                                tone={visual.tone}
                                trend={visual.trend}
                            />
                        );
                    })}
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="rounded-2xl shadow-sm xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Alerts</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div>
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Low stock</h3>
                                <div className="space-y-3">
                                    {(metrics.alerts?.low_stock ?? metrics.alerts?.low_retail_stock ?? []).length ? (
                                        (metrics.alerts?.low_stock ?? metrics.alerts?.low_retail_stock ?? []).map((item: any) => (
                                            <div key={`${item.sku}-${item.location_name}-${item.bucket}`} className="flex flex-col gap-2 rounded-xl border border-border/70 p-4 md:flex-row md:items-center md:justify-between">
                                                <div>
                                                    <p className="font-medium">
                                                        {item.brand_name} / {item.color}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {item.location_name} - {item.bucket} - {item.sku}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <StatusBadge status="pending" label="Low Stock" className="bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200" />
                                                    <span className="text-sm font-semibold">{item.quantity} rolls</span>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <EmptyState title="No low stock alerts" description="All monitored stock buckets are currently above threshold." />
                                    )}
                                </div>
                            </div>

                            <div>
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Out of stock</h3>
                                <div className="space-y-3">
                                    {(metrics.alerts?.out_of_stock ?? metrics.alerts?.out_of_stock_retail ?? []).length ? (
                                        (metrics.alerts?.out_of_stock ?? metrics.alerts?.out_of_stock_retail ?? []).map((item: any) => (
                                            <div key={`${item.sku}-${item.location_name}-${item.bucket}`} className="flex flex-col gap-2 rounded-xl border border-border/70 p-4 md:flex-row md:items-center md:justify-between">
                                                <div>
                                                    <p className="font-medium">
                                                        {item.brand_name} / {item.color}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {item.location_name} - {item.bucket} - {item.sku}
                                                    </p>
                                                </div>
                                                <StatusBadge status="out_of_stock" label="Out of Stock" />
                                            </div>
                                        ))
                                    ) : (
                                        <EmptyState title="No out of stock alerts" description="No stock bucket is currently at zero quantity." />
                                    )}
                                </div>
                            </div>

                            {metrics.alerts?.variance?.length ? (
                                <div>
                                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Variance alerts</h3>
                                    <div className="space-y-3">
                                        {metrics.alerts.variance.map((item: any) => (
                                            <Link
                                                key={item.id}
                                                href={route('transfers.show', item.id)}
                                                className="flex items-center justify-between rounded-xl border border-border/70 p-4 transition hover:bg-muted/40"
                                            >
                                                <div>
                                                    <p className="font-medium">{item.code}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        Closed {item.closed_at ? new Date(item.closed_at).toLocaleDateString() : 'recently'}
                                                    </p>
                                                </div>
                                                <StatusBadge status={item.status} />
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl shadow-sm">
                        <CardHeader>
                            <CardTitle>Quick insight</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            {metrics.summary?.warehouse_stock !== undefined ? (
                                <div className="rounded-xl border border-border/70 p-4">
                                    <div className="text-muted-foreground">Warehouse stock units</div>
                                    <div className="mt-2 text-2xl font-semibold">{Number(metrics.summary.warehouse_stock).toLocaleString()}</div>
                                </div>
                            ) : null}

                            {metrics.summary?.order_stats ? (
                                <div className="rounded-xl border border-border/70 p-4">
                                    <div className="text-muted-foreground">Order mix</div>
                                    <div className="mt-3 space-y-2">
                                        {Object.entries(metrics.summary.order_stats).map(([status, total]) => (
                                            <div key={status} className="flex items-center justify-between">
                                                <StatusBadge status={status} />
                                                <span className="font-medium">{String(total)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {metrics.summary?.invoice_stats ? (
                                <div className="rounded-xl border border-border/70 p-4">
                                    <div className="text-muted-foreground">Invoice mix</div>
                                    <div className="mt-3 space-y-2">
                                        {Object.entries(metrics.summary.invoice_stats).map(([status, total]) => (
                                            <div key={status} className="flex items-center justify-between">
                                                <StatusBadge status={status} />
                                                <span className="font-medium">{String(total)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {metrics.activity?.length ? (
                                <div className="rounded-xl border border-border/70 p-4">
                                    <div className="mb-3 text-muted-foreground">Recent transfer activity</div>
                                    <div className="space-y-3">
                                        {metrics.activity.map((transfer: any) => (
                                            <Link
                                                key={transfer.id}
                                                href={route('transfers.show', transfer.id)}
                                                className="block rounded-lg border border-border/60 p-3 transition hover:bg-muted/40"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className="font-medium">{transfer.code}</span>
                                                    <StatusBadge status={transfer.status} />
                                                </div>
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    {transfer.destination_location?.name ?? transfer.destinationLocation?.name ?? 'Destination pending'}
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {metrics.invoices?.length ? (
                                <div className="rounded-xl border border-border/70 p-4">
                                    <div className="mb-3 text-muted-foreground">Recent invoices</div>
                                    <div className="space-y-3">
                                        {metrics.invoices.map((invoice: any) => (
                                            <Link key={invoice.id} href={route('invoices.show', invoice.id)} className="block rounded-lg border border-border/60 p-3 transition hover:bg-muted/40">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-medium">{invoice.invoice_number}</span>
                                                    <StatusBadge status={invoice.payment_status} />
                                                </div>
                                                <div className="mt-1 text-xs text-muted-foreground">{formatCurrencyTZS(invoice.total_tzs)}</div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
