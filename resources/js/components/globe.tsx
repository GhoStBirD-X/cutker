import createGlobe from 'cobe';
import type { COBEOptions } from 'cobe';
import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

/** Lokasi pabrik (Bekasi / Cikarang), ditandai sebagai titik di globe. */
const MARKERS: COBEOptions['markers'] = [
    { location: [-6.3, 107.15], size: 0.06 },
];

/** Sudut awal supaya Bekasi / Cikarang langsung menghadap ke depan. */
const PHI_AWAL = Math.PI - ((107.15 * Math.PI) / 180 - Math.PI / 2);
const THETA = 0.12;

function warnaTema(
    gelap: boolean,
): Pick<
    COBEOptions,
    'dark' | 'baseColor' | 'markerColor' | 'glowColor' | 'mapBrightness'
> {
    return gelap
        ? {
              dark: 1,
              baseColor: [0.35, 0.3, 0.5],
              markerColor: [0.75, 0.6, 1],
              glowColor: [0.35, 0.28, 0.55],
              mapBrightness: 5,
          }
        : {
              dark: 0,
              baseColor: [1, 1, 1],
              markerColor: [0.42, 0.28, 0.78],
              glowColor: [0.92, 0.9, 1],
              mapBrightness: 6,
          };
}

/**
 * Globe 3D (COBE/WebGL) dekoratif untuk halaman welcome: berputar pelan,
 * bisa diputar dengan drag, dan warnanya mengikuti tema terang/gelap.
 * Rotasi otomatis dimatikan bila pengguna memilih "reduce motion".
 */
export function Globe({ className }: { className?: string }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const dragRef = useRef<{ x: number; phiSaatMulai: number } | null>(null);
    const phiRef = useRef(PHI_AWAL);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const html = document.documentElement;
        const kurangiGerak = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        let lebar = canvas.offsetWidth;
        let frame = 0;

        const globe = createGlobe(canvas, {
            devicePixelRatio: Math.min(window.devicePixelRatio, 2),
            width: lebar * 2,
            height: lebar * 2,
            phi: phiRef.current,
            theta: THETA,
            diffuse: 1.2,
            mapSamples: 16000,
            markers: MARKERS,
            ...warnaTema(html.classList.contains('dark')),
        });

        const putar = () => {
            if (!dragRef.current && !kurangiGerak) {
                phiRef.current += 0.003;
            }

            globe.update({ phi: phiRef.current });
            frame = requestAnimationFrame(putar);
        };
        frame = requestAnimationFrame(putar);

        const ubahUkuran = new ResizeObserver(() => {
            lebar = canvas.offsetWidth;
            globe.update({ width: lebar * 2, height: lebar * 2 });
        });
        ubahUkuran.observe(canvas);

        const gantiTema = new MutationObserver(() =>
            globe.update(warnaTema(html.classList.contains('dark'))),
        );
        gantiTema.observe(html, {
            attributes: true,
            attributeFilter: ['class'],
        });

        canvas.style.opacity = '1';

        return () => {
            cancelAnimationFrame(frame);
            ubahUkuran.disconnect();
            gantiTema.disconnect();
            globe.destroy();
        };
    }, []);

    return (
        <div className={cn('relative aspect-square w-full', className)}>
            <canvas
                ref={canvasRef}
                aria-hidden
                className="size-full cursor-grab opacity-0 transition-opacity duration-1000 [contain:layout_paint_size] active:cursor-grabbing"
                onPointerDown={(e) => {
                    dragRef.current = {
                        x: e.clientX,
                        phiSaatMulai: phiRef.current,
                    };
                    e.currentTarget.setPointerCapture(e.pointerId);
                }}
                onPointerMove={(e) => {
                    if (dragRef.current) {
                        phiRef.current =
                            dragRef.current.phiSaatMulai +
                            (e.clientX - dragRef.current.x) / 200;
                    }
                }}
                onPointerUp={() => {
                    dragRef.current = null;
                }}
                onPointerCancel={() => {
                    dragRef.current = null;
                }}
            />
        </div>
    );
}
