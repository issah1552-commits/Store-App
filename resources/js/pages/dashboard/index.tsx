import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { StatCard, type StatCardTone, type StatCardTrendDirection } from '@/components/shared/stat-card';
import { StatusBadge } from '@/components/shared/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectSeparator, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatCurrencyTZS } from '@/lib/format';
import { clickableLinkClassName } from '@/lib/link-styles';
import { type BreadcrumbItem, type LocationSummary } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeftRight, Box, Building2, DollarSign, TriangleAlert, type LucideIcon } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

type DashboardCardVisual = {
    icon: LucideIcon;
    tone: StatCardTone;
    trend: {
        value: string;
        direction: StatCardTrendDirection;
    };
};

type ChartDatum = {
    label: string;
    value: number;
};

type RecentActivity = {
    id: string;
    type: string;
    description: string;
    location: string;
    time?: string | null;
    status: string;
};

type AlertFilter = 'low-stock' | 'out-of-stock';

const WEEKDAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function toChartData(items: any[] | undefined): ChartDatum[] {
    return (items ?? [])
        .map((item) => ({
            label: String(item.label ?? ''),
            value: Number(item.value ?? 0),
        }))
        .filter((item) => item.label.length > 0);
}

function formatChartValue(value: number): string {
    if (value >= 1_000_000) {
        return `${Number((value / 1_000_000).toFixed(value >= 10_000_000 ? 0 : 1))}M`;
    }

    if (value >= 1_000) {
        return `${Number((value / 1_000).toFixed(value >= 10_000 ? 0 : 1))}k`;
    }

    return value.toLocaleString();
}

function formatDashboardCardValue(card: any): string | number {
    const label = String(card.label ?? '').toLowerCase();
    const value = Number(card.value ?? 0);

    if (label.includes('total sales') || label.includes('amount invoiced')) {
        return formatCurrencyTZS(value);
    }

    return Number.isFinite(value) ? value.toLocaleString() : String(card.value ?? '0');
}

function truncateChartLabel(label: string): string {
    return label.length > 13 ? `${label.slice(0, 12)}...` : label;
}

function chartTicks(maxValue: number): number[] {
    const safeMax = Math.max(maxValue, 1);

    return Array.from({ length: 5 }, (_, index) => Math.round(safeMax - (safeMax / 4) * index));
}

function formatRelativeTime(value?: string | null): string {
    if (!value) {
        return 'recently';
    }

    const timestamp = new Date(value).getTime();

    if (Number.isNaN(timestamp)) {
        return 'recently';
    }

    const seconds = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));

    if (seconds < 60) {
        return 'just now';
    }

    const units = [
        { label: 'year', seconds: 31_536_000 },
        { label: 'month', seconds: 2_592_000 },
        { label: 'day', seconds: 86_400 },
        { label: 'hour', seconds: 3_600 },
        { label: 'min', seconds: 60 },
    ];
    const unit = units.find((item) => seconds >= item.seconds) ?? units[units.length - 1];
    const amount = Math.floor(seconds / unit.seconds);

    return `${amount} ${unit.label}${amount === 1 || unit.label === 'min' ? '' : 's'} ago`;
}

function SalesOverviewChart({ data }: { data: ChartDatum[] }) {
    const chartData = data.length ? data : WEEKDAY_LABELS.map((label) => ({ label, value: 0 }));
    const width = 640;
    const height = 300;
    const padding = { top: 16, right: 28, bottom: 42, left: 62 };
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const maxValue = Math.max(...chartData.map((item) => item.value), 1);
    const yMax = Math.ceil(maxValue * 1.15);
    const bottomY = padding.top + innerHeight;
    const points = chartData.map((item, index) => {
        const x = padding.left + (chartData.length === 1 ? innerWidth / 2 : (innerWidth / (chartData.length - 1)) * index);
        const y = bottomY - (item.value / yMax) * innerHeight;

        return { ...item, x, y };
    });
    const linePath = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
    const areaPath = `${linePath} L ${points[points.length - 1].x} ${bottomY} L ${points[0].x} ${bottomY} Z`;

    return (
        <Card className="rounded-2xl border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <CardHeader>
                <CardTitle>Sales Overview</CardTitle>
            </CardHeader>
            <CardContent>
                <svg
                    viewBox={`0 0 ${width} ${height}`}
                    role="img"
                    aria-label="Sales overview by day of the week"
                    className="h-[300px] w-full overflow-visible"
                >
                    <defs>
                        <linearGradient id="sales-overview-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stopColor="rgb(59 130 246)" stopOpacity="0.22" />
                            <stop offset="100%" stopColor="rgb(59 130 246)" stopOpacity="0.02" />
                        </linearGradient>
                    </defs>

                    {chartTicks(yMax).map((tick) => {
                        const y = bottomY - (tick / yMax) * innerHeight;

                        return (
                            <g key={tick}>
                                <line
                                    x1={padding.left}
                                    x2={width - padding.right}
                                    y1={y}
                                    y2={y}
                                    className="stroke-slate-200 dark:stroke-slate-800"
                                    strokeDasharray="5 5"
                                />
                                <text x={padding.left - 12} y={y + 5} textAnchor="end" className="fill-slate-500 text-[13px] dark:fill-slate-400">
                                    {formatChartValue(tick)}
                                </text>
                            </g>
                        );
                    })}

                    {points.map((point) => (
                        <line
                            key={`x-${point.label}`}
                            x1={point.x}
                            x2={point.x}
                            y1={padding.top}
                            y2={bottomY}
                            className="stroke-slate-100 dark:stroke-slate-900"
                            strokeDasharray="5 5"
                        />
                    ))}

                    <path d={areaPath} fill="url(#sales-overview-fill)" />
                    <path d={linePath} className="fill-none stroke-blue-500" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" />

                    {points.map((point) => (
                        <g key={`point-${point.label}`}>
                            <circle cx={point.x} cy={point.y} r="6" className="fill-white stroke-blue-500 dark:fill-slate-950" strokeWidth="4">
                                <title>
                                    {point.label}: {formatChartValue(point.value)}
                                </title>
                            </circle>
                            <text x={point.x} y={bottomY + 28} textAnchor="middle" className="fill-slate-500 text-[15px] dark:fill-slate-400">
                                {point.label}
                            </text>
                        </g>
                    ))}
                </svg>
            </CardContent>
        </Card>
    );
}

function StockDistributionChart({ data }: { data: ChartDatum[] }) {
    const chartData = data.length ? data : [{ label: 'No stock', value: 0 }];
    const width = 640;
    const height = 300;
    const padding = { top: 16, right: 28, bottom: 48, left: 58 };
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const maxValue = Math.max(...chartData.map((item) => item.value), 1);
    const yMax = Math.ceil(maxValue * 1.1);
    const bottomY = padding.top + innerHeight;
    const slotWidth = innerWidth / chartData.length;
    const barWidth = Math.min(84, slotWidth * 0.62);

    return (
        <Card className="rounded-2xl border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <CardHeader>
                <CardTitle>Stock Distribution</CardTitle>
            </CardHeader>
            <CardContent>
                <svg viewBox={`0 0 ${width} ${height}`} role="img" aria-label="Stock distribution" className="h-[300px] w-full overflow-visible">
                    {chartTicks(yMax).map((tick) => {
                        const y = bottomY - (tick / yMax) * innerHeight;

                        return (
                            <g key={tick}>
                                <line
                                    x1={padding.left}
                                    x2={width - padding.right}
                                    y1={y}
                                    y2={y}
                                    className="stroke-slate-200 dark:stroke-slate-800"
                                    strokeDasharray="5 5"
                                />
                                <text x={padding.left - 12} y={y + 5} textAnchor="end" className="fill-slate-500 text-[13px] dark:fill-slate-400">
                                    {formatChartValue(tick)}
                                </text>
                            </g>
                        );
                    })}

                    {chartData.map((item, index) => {
                        const x = padding.left + slotWidth * index + (slotWidth - barWidth) / 2;
                        const barHeight = (item.value / yMax) * innerHeight;
                        const y = bottomY - barHeight;

                        return (
                            <g key={`${item.label}-${index}`}>
                                <rect x={x} y={y} width={barWidth} height={barHeight} rx="12" className="fill-blue-500">
                                    <title>
                                        {item.label}: {formatChartValue(item.value)}
                                    </title>
                                </rect>
                                <text
                                    x={x + barWidth / 2}
                                    y={bottomY + 30}
                                    textAnchor="middle"
                                    className="fill-slate-500 text-[15px] dark:fill-slate-400"
                                >
                                    {truncateChartLabel(item.label)}
                                </text>
                            </g>
                        );
                    })}
                </svg>
            </CardContent>
        </Card>
    );
}

function DashboardCharts({ salesData, stockData }: { salesData: ChartDatum[]; stockData: ChartDatum[] }) {
    return (
        <div className="grid gap-5 xl:grid-cols-2">
            <SalesOverviewChart data={salesData} />
            <StockDistributionChart data={stockData} />
        </div>
    );
}

function AlertFilterToggle({
    value,
    onChange,
    lowStockCount,
    outOfStockCount,
}: {
    value: AlertFilter;
    onChange: (value: AlertFilter) => void;
    lowStockCount: number;
    outOfStockCount: number;
}) {
    const options: { count: number; label: string; value: AlertFilter }[] = [
        { count: lowStockCount, label: 'Low Stock', value: 'low-stock' },
        { count: outOfStockCount, label: 'Out of Stock', value: 'out-of-stock' },
    ];

    return (
        <div className="border-border bg-muted/70 dark:bg-muted/40 inline-flex max-w-full items-center gap-2 rounded-[1.35rem] border p-2 text-sm font-semibold shadow-sm">
            <div className="border-border bg-background text-muted-foreground flex h-11 w-11 shrink-0 items-center justify-center rounded-[1rem] border">
                <TriangleAlert className="h-5 w-5" />
            </div>
            <div className="flex min-w-0 gap-1">
                {options.map((option) => {
                    const isActive = value === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={isActive}
                            onClick={() => onChange(option.value)}
                            className={[
                                'flex h-11 items-center gap-2 rounded-[1rem] px-4 text-sm font-semibold whitespace-nowrap transition',
                                isActive
                                    ? 'bg-primary text-primary-foreground shadow-sm'
                                    : 'text-muted-foreground hover:bg-background hover:text-foreground',
                            ].join(' ')}
                        >
                            <span>{option.label}</span>
                            <span
                                className={[
                                    'flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold tabular-nums',
                                    isActive ? 'bg-primary-foreground/15 text-primary-foreground' : 'bg-background text-foreground',
                                ].join(' ')}
                            >
                                {option.count}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function RecentActivitiesSection({ activities }: { activities: RecentActivity[] }) {
    return (
        <Card className="rounded-2xl shadow-sm">
            <CardHeader>
                <CardTitle>Recent Activities</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                {activities.length ? (
                    <div className="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Type</th>
                                    <th className="px-6 py-4 font-semibold">Description</th>
                                    <th className="px-6 py-4 font-semibold">Location</th>
                                    <th className="px-6 py-4 font-semibold">Time</th>
                                    <th className="px-6 py-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {activities.map((activity) => (
                                    <tr key={activity.id} className="border-border/70 border-t">
                                        <td className="px-6 py-4 font-medium">{activity.type}</td>
                                        <td className="px-6 py-4 text-slate-700 dark:text-slate-200">{activity.description}</td>
                                        <td className="px-6 py-4 text-slate-700 dark:text-slate-200">{activity.location}</td>
                                        <td className="text-muted-foreground px-6 py-4">{formatRelativeTime(activity.time)}</td>
                                        <td className="px-6 py-4">
                                            <StatusBadge status={activity.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="px-6 pb-6">
                        <EmptyState title="No recent activities" description="New orders, transfers, and invoices will appear here." />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function LowStockPanel({ items }: { items: any[] }) {
    return (
        <div className="flex h-full min-h-0 flex-col rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
            <h3 className="text-muted-foreground mb-3 text-sm font-semibold tracking-wide uppercase">Low stock</h3>
            <div className="min-h-0 flex-1 overflow-y-auto pr-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div className="space-y-3">
                    {items.length ? (
                        items.map((item: any) => (
                            <div
                                key={`${item.sku}-${item.location_name}-${item.bucket}`}
                                className="border-border/70 flex flex-col gap-3 rounded-xl border p-4"
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {item.brand_name} / {item.color}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {item.location_name} - {item.bucket} - {item.sku}
                                    </p>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <StatusBadge
                                        status="pending"
                                        label="Low Stock"
                                        className="bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200"
                                    />
                                    <span className="text-sm font-semibold whitespace-nowrap">{item.quantity} rolls</span>
                                </div>
                            </div>
                        ))
                    ) : (
                        <EmptyState title="No low stock alerts" description="All monitored stock buckets are currently above threshold." />
                    )}
                </div>
            </div>
        </div>
    );
}

function OutOfStockPanel({ items }: { items: any[] }) {
    return (
        <div className="flex h-full min-h-0 flex-col rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
            <h3 className="text-muted-foreground mb-3 text-sm font-semibold tracking-wide uppercase">Out of stock</h3>
            <div className="min-h-0 flex-1 overflow-y-auto pr-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div className="space-y-3">
                    {items.length ? (
                        items.map((item: any) => (
                            <div
                                key={`${item.sku}-${item.location_name}-${item.bucket}`}
                                className="border-border/70 flex flex-col gap-3 rounded-xl border p-4"
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {item.brand_name} / {item.color}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {item.location_name} - {item.bucket} - {item.sku}
                                    </p>
                                </div>
                                <div className="flex justify-start">
                                    <StatusBadge status="out_of_stock" label="Out of Stock" />
                                </div>
                            </div>
                        ))
                    ) : (
                        <EmptyState title="No out of stock alerts" description="No stock bucket is currently at zero quantity." />
                    )}
                </div>
            </div>
        </div>
    );
}

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
    const [activeAlertFilter, setActiveAlertFilter] = useState<AlertFilter>('low-stock');
    const selectedLocation = filters.location_id ? String(filters.location_id) : 'all';
    const activeLocation = locations.find((location) => String(location.id) === selectedLocation) ?? null;

    const warehouseLocations = locations.filter((location) => location.type === 'warehouse');
    const shopLocations = locations.filter((location) => location.type === 'shop');
    const salesOverviewData = toChartData(metrics.charts?.sales_overview);
    const stockDistributionData = toChartData(metrics.charts?.stock_distribution);
    const lowStockItems = metrics.alerts?.low_stock ?? metrics.alerts?.low_retail_stock ?? [];
    const outOfStockItems = metrics.alerts?.out_of_stock ?? metrics.alerts?.out_of_stock_retail ?? [];
    const recentActivities = (metrics.recent_activities ?? []) as RecentActivity[];

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
                            <div className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-blue-50 px-4 text-sm font-semibold tracking-[0.18em] text-blue-600 uppercase dark:bg-blue-950/40 dark:text-blue-300">
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
                                value={formatDashboardCardValue(card)}
                                icon={visual.icon}
                                tone={visual.tone}
                                trend={visual.trend}
                            />
                        );
                    })}
                </div>

                <DashboardCharts salesData={salesOverviewData} stockData={stockDistributionData} />

                <div className="grid items-stretch gap-4 xl:grid-cols-3">
                    <Card className="flex min-h-[34rem] flex-col rounded-2xl shadow-sm xl:col-span-2 xl:h-[48rem]">
                        <CardHeader className="flex flex-col gap-4 space-y-0 md:flex-row md:items-center md:justify-between">
                            <CardTitle>Alerts</CardTitle>
                            <AlertFilterToggle
                                value={activeAlertFilter}
                                onChange={setActiveAlertFilter}
                                lowStockCount={lowStockItems.length}
                                outOfStockCount={outOfStockItems.length}
                            />
                        </CardHeader>
                        <CardContent className="min-h-0 flex-1 overflow-hidden">
                            {activeAlertFilter === 'low-stock' ? (
                                <LowStockPanel items={lowStockItems} />
                            ) : (
                                <OutOfStockPanel items={outOfStockItems} />
                            )}
                        </CardContent>
                    </Card>

                    <Card className="flex min-h-[34rem] flex-col rounded-2xl shadow-sm xl:h-[48rem]">
                        <CardHeader>
                            <CardTitle>Quick insight</CardTitle>
                        </CardHeader>
                        <CardContent className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-5 text-sm [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            {metrics.summary?.warehouse_stock !== undefined ? (
                                <div className="border-border/70 rounded-xl border p-4">
                                    <div className="text-muted-foreground">Warehouse stock units</div>
                                    <div className="mt-2 text-2xl font-semibold">{Number(metrics.summary.warehouse_stock).toLocaleString()}</div>
                                </div>
                            ) : null}

                            {metrics.summary?.order_stats ? (
                                <div className="border-border/70 rounded-xl border p-4">
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
                                <div className="border-border/70 rounded-xl border p-4">
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
                                <div className="border-border/70 rounded-xl border p-4">
                                    <div className="text-muted-foreground mb-3">Recent transfer activity</div>
                                    <div className="space-y-3">
                                        {metrics.activity.map((transfer: any) => (
                                            <Link
                                                key={transfer.id}
                                                href={route('transfers.show', transfer.id)}
                                                className="border-border/60 hover:bg-muted/40 block rounded-lg border p-3 transition"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className={clickableLinkClassName}>{transfer.code}</span>
                                                    <StatusBadge status={transfer.status} />
                                                </div>
                                                <div className="text-muted-foreground mt-1 text-xs">
                                                    {transfer.destination_location?.name ??
                                                        transfer.destinationLocation?.name ??
                                                        'Destination pending'}
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {metrics.invoices?.length ? (
                                <div className="border-border/70 rounded-xl border p-4">
                                    <div className="text-muted-foreground mb-3">Recent invoices</div>
                                    <div className="space-y-3">
                                        {metrics.invoices.map((invoice: any) => (
                                            <Link
                                                key={invoice.id}
                                                href={route('invoices.show', invoice.id)}
                                                className="border-border/60 hover:bg-muted/40 block rounded-lg border p-3 transition"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className={clickableLinkClassName}>{invoice.invoice_number}</span>
                                                    <StatusBadge status={invoice.payment_status} />
                                                </div>
                                                <div className="text-muted-foreground mt-1 text-xs">{formatCurrencyTZS(invoice.total_tzs)}</div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>

                <RecentActivitiesSection activities={recentActivities} />
            </div>
        </AppLayout>
    );
}
