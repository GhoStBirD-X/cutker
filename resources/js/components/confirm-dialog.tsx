import { useSyncExternalStore } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type KonfirmasiOptions = {
    title: string;
    description?: React.ReactNode;
    confirmText?: string;
    cancelText?: string;
    /** Tombol konfirmasi berwarna merah untuk aksi yang menghapus/tidak bisa dibatalkan. */
    destructive?: boolean;
};

type KonfirmasiState = KonfirmasiOptions & {
    resolve: (confirmed: boolean) => void;
};

let state: KonfirmasiState | null = null;
const listeners = new Set<() => void>();

const setState = (next: KonfirmasiState | null): void => {
    state = next;
    listeners.forEach((listener) => listener());
};

const subscribe = (listener: () => void): (() => void) => {
    listeners.add(listener);

    return () => listeners.delete(listener);
};

/**
 * Pengganti `window.confirm()` bawaan browser dengan dialog yang konsisten
 * dengan UI aplikasi. Resolve `true` jika pengguna menekan tombol konfirmasi.
 */
export function konfirmasi(options: KonfirmasiOptions): Promise<boolean> {
    state?.resolve(false);

    return new Promise((resolve) => setState({ ...options, resolve }));
}

/** Dipasang sekali di root aplikasi (lihat `app.tsx`). */
export function ConfirmDialogHost() {
    const current = useSyncExternalStore(
        subscribe,
        () => state,
        () => null,
    );

    const tutup = (confirmed: boolean) => {
        current?.resolve(confirmed);
        setState(null);
    };

    return (
        <Dialog
            open={current !== null}
            onOpenChange={(open) => !open && tutup(false)}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{current?.title}</DialogTitle>
                    {current?.description && (
                        <DialogDescription>
                            {current.description}
                        </DialogDescription>
                    )}
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <Button variant="secondary" onClick={() => tutup(false)}>
                        {current?.cancelText ?? 'Batal'}
                    </Button>
                    <Button
                        variant={
                            current?.destructive ? 'destructive' : 'default'
                        }
                        onClick={() => tutup(true)}
                        autoFocus
                    >
                        {current?.confirmText ?? 'Ya, lanjutkan'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
