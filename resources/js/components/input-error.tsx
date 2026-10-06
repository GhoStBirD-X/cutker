import { useEffect, useId, useRef } from 'react';
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

const SELEKTOR_KONTROL =
    'input:not([type="hidden"]), textarea, select, button[role="combobox"]';

/**
 * Pesan galat validasi di bawah field. Selain menampilkan teks, komponen ini
 * menandai kontrol pertama dalam wadah field yang sama dengan `aria-invalid`
 * (memberi ring merah lewat style bawaan input) dan `aria-describedby`
 * (supaya pembaca layar ikut membacakan pesannya) — tanpa perlu mengubah
 * setiap pemanggil satu per satu.
 */
export default function InputError({
    message,
    className = '',
    id,
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const generatedId = useId();
    const errorId = id ?? generatedId;
    const ref = useRef<HTMLParagraphElement>(null);

    useEffect(() => {
        const kontrol =
            ref.current?.parentElement?.querySelector<HTMLElement>(
                SELEKTOR_KONTROL,
            );

        if (!kontrol) {
            return;
        }

        // Pemanggil yang sudah mengatur aria-invalid sendiri tetap berkuasa.
        const tandaiInvalid = !kontrol.hasAttribute('aria-invalid');

        if (tandaiInvalid) {
            kontrol.setAttribute('aria-invalid', 'true');
        }

        kontrol.setAttribute('aria-describedby', errorId);

        return () => {
            if (tandaiInvalid) {
                kontrol.removeAttribute('aria-invalid');
            }

            kontrol.removeAttribute('aria-describedby');
        };
    }, [message, errorId]);

    return message ? (
        <p
            {...props}
            ref={ref}
            id={errorId}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {message}
        </p>
    ) : null;
}
