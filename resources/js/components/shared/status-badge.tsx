import type { ReactNode } from 'react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const badgeStyles: Record<string, string> = {
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    approved: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    dispatched: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    escalated: 'bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    pending: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
    pending_approval: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
    partially_dispatched: 'bg-yellow-100 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-200',
    partially_paid: 'bg-yellow-100 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-200',
    partially_received: 'bg-yellow-100 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-200',
    received: 'bg-cyan-100 text-cyan-900 dark:bg-cyan-950 dark:text-cyan-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    reversed: 'bg-violet-100 text-violet-900 dark:bg-violet-950 dark:text-violet-200',
    unpaid: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    void: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    closed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    closed_with_variance: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    inactive: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    out_of_stock: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
};

function titleCase(value: string) {
    return value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function StatusBadge({
    status,
    label,
    children,
    className = '',
}: {
    status: string;
    label?: string;
    children?: ReactNode;
    className?: string;
}) {
    return (
        <Badge variant="secondary" className={cn('border-0 capitalize', badgeStyles[status] ?? badgeStyles.pending, className)}>
            {children ?? label ?? titleCase(status)}
        </Badge>
    );
}
