<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'default' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.17 0.016 295);
            }

            html[data-theme='retro'] {
                background-color: oklch(0.97 0.02 75);
            }

            html[data-theme='retro'].dark {
                background-color: oklch(0.2 0.025 50);
            }

            html[data-theme='ocean'] {
                background-color: oklch(0.98 0.008 220);
            }

            html[data-theme='ocean'].dark {
                background-color: oklch(0.16 0.026 235);
            }

            html[data-theme='forest'] {
                background-color: oklch(0.98 0.01 130);
            }

            html[data-theme='forest'].dark {
                background-color: oklch(0.17 0.02 150);
            }

            html[data-theme='pixel'] {
                background-color: oklch(0.88 0.03 130);
            }

            html[data-theme='pixel'].dark {
                background-color: oklch(0.14 0.02 265);
            }

            html[data-theme='neubrutalism'] {
                background-color: oklch(0.98 0 0);
            }

            html[data-theme='neubrutalism'].dark {
                background-color: oklch(0.12 0 0);
            }

            html[data-theme='terminal'] {
                background-color: oklch(0.97 0.01 145);
            }

            html[data-theme='terminal'].dark {
                background-color: oklch(0.08 0.01 150);
            }

            html[data-theme='glass'] {
                background-color: oklch(0.93 0.03 280);
            }

            html[data-theme='glass'].dark {
                background-color: oklch(0.22 0.05 280);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{--
            Cuma load font dasar + font milik tema warna yang lagi aktif,
            supaya HP/koneksi lambat tidak menarik font tema lain yang
            sedang tidak dipakai (mis. Press Start 2P/VT323 untuk tema
            Pixel, JetBrains Mono untuk tema Terminal).
        --}}
        @php
            $themeFontAliases = match ($theme ?? 'default') {
                'pixel' => ['press-start-2p', 'vt323'],
                'terminal' => ['jetbrains-mono'],
                default => [],
            };
        @endphp
        @fonts(array_merge(['instrument-sans'], $themeFontAliases))

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
