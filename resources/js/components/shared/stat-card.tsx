import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { ArrowDownRight, ArrowUpRight, type LucideIcon } from 'lucide-react';

export type StatCardTone = 'blue' | 'orange' | 'green' | 'purple';
export type StatCardTrendDirection = 'positive' | 'negative';

type StatCardProps = {
    title: string;
    value: string | number;
    icon: LucideIcon;
    tone: StatCardTone;
    trend?: {
        value: string;
        direction: StatCardTrendDirection;
    };
    className?: string;
};

const toneClasses: Record<
    StatCardTone,
    {
        iconWrapper: string;
        icon: string;
    }
> = {
    blue: {
        iconWrapper: 'bg-blue-50 dark:bg-blue-500/10',
        icon: 'text-blue-600 dark:text-blue-300',
    },
    orange: {
        iconWrapper: 'bg-orange-50 dark:bg-orange-500/10',
        icon: 'text-orange-500 dark:text-orange-300',
    },
    green: {
        iconWrapper: 'bg-emerald-50 dark:bg-emerald-500/10',
        icon: 'text-emerald-600 dark:text-emerald-300',
    },
    purple: {
        iconWrapper: 'bg-violet-50 dark:bg-violet-500/10',
        icon: 'text-violet-600 dark:text-violet-300',
    },
};

export function StatCard({ title, value, icon: Icon, tone, trend, className }: StatCardProps) {
    const palette = toneClasses[tone];
    const TrendIcon = trend?.direction === 'negative' ? ArrowDownRight : ArrowUpRight;

    return (
        <Card
            className={cn(
                'rounded-2xl border border-slate-200/90 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.06)] transition-all duration-200 hover:scale-[1.03] hover:shadow-[0_18px_40px_rgba(15,23,42,0.10)] dark:border-slate-800 dark:bg-slate-950 dark:shadow-[0_12px_32px_rgba(2,6,23,0.32)]',
                className,
            )}
        >
            <CardContent className="flex min-h-[208px] flex-col justify-between p-5 md:p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className={cn('flex h-14 w-14 items-center justify-center rounded-2xl', palette.iconWrapper)}>
                        <Icon className={cn('h-6 w-6', palette.icon)} />
                    </div>

                    {trend ? (
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 text-sm font-medium',
                                trend.direction === 'positive' ? 'text-emerald-600 dark:text-emerald-300' : 'text-red-500 dark:text-red-300',
                            )}
                        >
                            <TrendIcon className="h-4 w-4" />
                            <span>{trend.value}</span>
                        </div>
                    ) : null}
                </div>

                <div className="mt-8 space-y-3">
                    <p className="text-[15px] font-medium text-slate-500 dark:text-slate-400">{title}</p>
                    <div className="text-[2.15rem] font-bold tracking-[-0.04em] text-slate-900 dark:text-slate-50">{value}</div>
                </div>
            </CardContent>
        </Card>
    );
}
