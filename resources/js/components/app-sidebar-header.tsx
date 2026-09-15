import { Link } from '@inertiajs/react';
import { Settings } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationBell } from '@/components/notification-bell';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { edit as editProfile } from '@/routes/profile';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-1">
                <Button variant="ghost" size="icon" asChild>
                    <Link href={editProfile()} prefetch>
                        <Settings className="size-5" />
                    </Link>
                </Button>
                <ThemeToggle />
                <NotificationBell />
            </div>
        </header>
    );
}
