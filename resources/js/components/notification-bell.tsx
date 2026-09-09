import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { AppNotification } from '@/types';

type PageProps = {
    notifications: {
        unreadCount: number;
        recent: AppNotification[];
    };
};

export function NotificationBell() {
    const { notifications } = usePage<PageProps>().props;

    const bacaSatu = (notification: AppNotification) => {
        if (!notification.read_at) {
            router.post(
                `/notifications/${notification.id}/read`,
                {},
                { preserveScroll: true },
            );
        }
    };

    const bacaSemua = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="size-5" />
                    {notifications.unreadCount > 0 && (
                        <Badge className="absolute -top-1 -right-1 h-4 min-w-4 justify-center rounded-full px-1 text-[10px]">
                            {notifications.unreadCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel className="flex items-center justify-between">
                    Notifikasi
                    {notifications.unreadCount > 0 && (
                        <button
                            onClick={bacaSemua}
                            className="text-xs font-normal text-primary hover:underline"
                        >
                            Tandai semua dibaca
                        </button>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {notifications.recent.length === 0 && (
                    <div className="px-2 py-4 text-center text-sm text-muted-foreground">
                        Belum ada notifikasi.
                    </div>
                )}
                {notifications.recent.map((notification) => (
                    <DropdownMenuItem
                        key={notification.id}
                        onClick={() => bacaSatu(notification)}
                        className={
                            notification.read_at ? 'opacity-60' : 'font-medium'
                        }
                    >
                        {notification.data.message}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
