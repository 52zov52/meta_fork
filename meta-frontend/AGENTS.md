# AGENTS.md

## Tech Stack

- **Framework**: [Nuxt v4](https://github.com/nuxt/nuxt) with [Vue v3](https://github.com/vuejs/core) ([vuejs.org docs](https://github.com/vuejs/docs)) (Composition API)
- **UI Library**: [Nuxt UI v4](https://github.com/nuxt/ui)
- **CSS**: [Tailwind CSS v4](https://github.com/tailwindlabs/tailwindcss)
- **Auth**: [Nuxt Auth Sanctum](manchenkoff/nuxt-auth-sanctum) for Laravel Sanctum authentication
- **Charts**: [Unovis](https://github.com/f5/unovis)

## Important Notes

**SPA Mode** (`ssr: false`) - client-side only

**Auto-imports** — Enabled by default. Vue API (`ref`, `computed`, `onMounted`, etc.), components from `components/`, and composables from `composables/` are auto-imported; no config needed. Types are generated in `.nuxt/imports.d.ts`.

## Docker vs Host Commands

Toolchain commands may run either in the dev container (`nuxt-dev`) or on the host:

- `docker compose exec nuxt-dev sh -lc '<cmd>'` — in container
- `<cmd>` — on host

## Colors (Nuxt UI)

Palette in `app.config.ts` → `ui.colors`. In Tailwind use semantic classes: `text-primary`, `bg-primary`, `bg-default`, `border-default`, etc., not raw colors like `text-indigo-400`. If you need a shade (e.g. dark sidebar tint), use the semantic shade: `bg-primary-800` / `border-primary-700` instead of `bg-indigo-800` / `border-indigo-700`.

## API types (OpenAPI)

Generated from backend OpenAPI spec (Laravel Scribe) into `app/types/api.d.ts`. Regenerate after API changes: `pnpm run generate:api`.

Usage: import `paths` from `~/types/api`; define response type via `paths['/api/...']['get']['responses']['200']['content']['application/json']`; pass to `useApi().get<T>(url)`. Reference: `app/composables/usePortfolioList.ts`.

