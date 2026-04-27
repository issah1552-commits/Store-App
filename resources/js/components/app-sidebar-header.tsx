import { Breadcrumbs } from '@/components/breadcrumbs';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex items-center gap-3">
                <div className="hidden text-right md:block">
                    <div className="text-sm font-medium">{auth.user?.role?.display_name ?? 'User'}</div>
                    <div className="text-xs text-muted-foreground">{auth.user?.default_location?.name ?? 'No location'}</div>
                </div>
                <AppearanceToggleDropdown />
            </div>
        </header>
    );
}
