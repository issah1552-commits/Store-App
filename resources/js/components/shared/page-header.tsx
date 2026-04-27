import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { type ReactNode } from 'react';

export function PageHeader({
    title,
    description,
    actionLabel,
    actionHref,
    actions,
}: {
    title: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight text-foreground">{title}</h1>
                {description ? <p className="text-sm leading-6 text-muted-foreground">{description}</p> : null}
            </div>

            {actions ? (
                <div className="w-full sm:w-auto">{actions}</div>
            ) : actionLabel && actionHref ? (
                <Button asChild className="rounded-xl">
                    <Link href={actionHref}>
                        <Plus className="h-4 w-4" />
                        {actionLabel}
                    </Link>
                </Button>
            ) : null}
        </div>
    );
}
