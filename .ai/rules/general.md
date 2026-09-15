---
paths:
  - vite.config.ts
---

# General

## wayfinder:generate needs --with-form
`vite.config.ts` configures the wayfinder Vite plugin with `formVariants: true`, but that only applies when Vite itself runs (dev/build). Running `php artisan wayfinder:generate` directly via CLI (e.g. after adding a new route) regenerates ALL actions/routes files WITHOUT form variants by default, silently breaking every other page that uses `.form()` (dozens of TS errors across unrelated files). Always run `php artisan wayfinder:generate --with-form --no-interaction` when generating from the CLI instead of via `npm run dev`/`vite build`.
